<?php

namespace Modules\Vault\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Signal\Facades\Signal;
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

    private const AUTH_ROUTES = [
        'login',
        'register',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
        'password.confirm',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    /**
     * Enforce vault lock timeout and redirect to lock screen when expired.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! ModuleFacade::has('Vault') || ! ModuleFacade::isEnabled('Vault')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, self::EXCLUDED_ROUTES, true)) {
            return $next($request);
        }

        if ($this->isAuthRoute($routeName)) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user) {
            return $next($request);
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

                    $this->sendAutoLockNotification($user, $request);
                }
            }
        } elseif ($unlocked && (! $unlockedAt || ! is_numeric($unlockedAt) || $unlockedAt <= 0)) {
            $request->session()->forget('vault_unlocked');
            $request->session()->forget('vault_unlocked_at');
            $isLocked = true;
        }

        if ($isLocked && $this->isVaultRoute($routeName)) {
            if ($request->method() === 'GET') {
                $request->session()->put('vault_intended_url', $request->fullUrl());
            }

            return redirect()->route('vault.lock');
        }

        return $next($request);
    }

    /**
     * Check if route requires vault lock protection.
     */
    private function isVaultRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        if (in_array($routeName, self::EXCLUDED_ROUTES, true)) {
            return false;
        }

        return str_starts_with($routeName, 'vault.');
    }

    /**
     * Check if route should bypass vault lock.
     */
    private function isAuthRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        return in_array($routeName, self::AUTH_ROUTES, true);
    }

    /**
     * Send notification when vault auto-locks due to timeout.
     */
    private function sendAutoLockNotification($user, Request $request): void
    {
        try {
            $notificationKey = 'vault_autolock_notified';
            if ($request->session()->has($notificationKey)) {
                return;
            }

            $request->session()->put($notificationKey, true);

            if (! class_exists(Signal::class)) {
                return;
            }

            Signal::info(
                $user,
                'Vault Auto-Locked',
                'Your vault was automatically locked due to inactivity timeout.',
                route('vault.lock'),
                'Vault',
                'vault'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('VaultLockMiddleware: Failed to send auto-lock notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
