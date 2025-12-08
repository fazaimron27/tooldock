<?php

namespace Modules\Vault\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vault\Models\VaultLock;
use Nwidart\Modules\Facades\Module as ModuleFacade;
use Symfony\Component\HttpFoundation\Response;

class VaultLockMiddleware
{
    private const EXCLUDED_ROUTES = [
        'vault.lock',
        'vault.unlock',
        'vault.lock.store',
        'vault.pin.set',
        'vault.lock.status',
    ];

    /**
     * Handle an incoming request.
     *
     * Global middleware that runs on all requests. Checks timeout globally but redirects
     * only when accessing vault routes. If timeout expires on non-vault pages, silently
     * marks vault as locked and allows user to continue browsing.
     *
     * Safely handles cases where the Vault module is not installed or disabled.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! ModuleFacade::has('Vault') || ! ModuleFacade::isEnabled('Vault')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $path = $request->path();

        $isLockRoute = ($routeName && in_array($routeName, self::EXCLUDED_ROUTES, true))
            || str_contains($path, 'vault/lock');

        if ($isLockRoute) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $vaultLockEnabled = settings('vault_lock_enabled', false);
        $timeoutMinutes = max(1, min(settings('vault_lock_timeout', 15), 1440));

        if (! $vaultLockEnabled) {
            return $next($request);
        }

        try {
            $vaultLock = VaultLock::where('user_id', $user->id)->first();
        } catch (\Exception $e) {
            return $next($request);
        }

        if (! $vaultLock) {
            return $next($request);
        }

        $unlocked = (bool) $request->session()->get('vault_unlocked', false);
        $unlockedAt = $request->session()->get('vault_unlocked_at');
        $isLocked = ! $unlocked;

        if ($unlocked && $unlockedAt && is_numeric($unlockedAt) && $unlockedAt > 0 && $unlockedAt <= PHP_INT_MAX) {
            $unlockedAtCarbon = Carbon::createFromTimestamp($unlockedAt);

            if ($unlockedAtCarbon->isFuture()) {
                $request->session()->forget('vault_unlocked');
                $request->session()->forget('vault_unlocked_at');
                $isLocked = true;
            } else {
                $minutesSinceUnlock = $unlockedAtCarbon->diffInMinutes(now());

                if ($minutesSinceUnlock > $timeoutMinutes) {
                    $request->session()->forget('vault_unlocked');
                    $request->session()->forget('vault_unlocked_at');
                    $isLocked = true;
                }
            }
        } elseif ($unlocked && (! $unlockedAt || ! is_numeric($unlockedAt) || $unlockedAt <= 0)) {
            $request->session()->forget('vault_unlocked');
            $request->session()->forget('vault_unlocked_at');
            $isLocked = true;
        }

        if ($isLocked && $this->isVaultRoute($routeName, $path, $request)) {
            if ($request->method() === 'GET') {
                $request->session()->put('vault_intended_url', $request->fullUrl());
            }

            return redirect()->route('vault.lock');
        }

        return $next($request);
    }

    /**
     * Check if the current request is for a vault route that requires lock protection.
     *
     * Excludes unlock routes (lock screen, unlock action, PIN setup, etc.)
     *
     * @param  string|null  $routeName
     * @param  string  $path
     * @param  Request  $request
     * @return bool
     */
    private function isVaultRoute(?string $routeName, string $path, Request $request): bool
    {
        if (! $routeName) {
            $prefix = $request->route()?->getPrefix() ?? 'tooldock';
            $prefix = trim($prefix, '/');

            return str_starts_with($path, "{$prefix}/vault") && ! str_contains($path, 'vault/lock');
        }

        if (in_array($routeName, self::EXCLUDED_ROUTES, true)) {
            return false;
        }

        return str_starts_with($routeName, 'vault.');
    }
}
