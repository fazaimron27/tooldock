<?php

namespace Modules\Vault\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Vault\Models\VaultLock;
use Symfony\Component\HttpFoundation\Response;

class VaultLockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Checks if vault lock is enabled and if the user has unlocked the vault.
     * Redirects to lock screen if vault is locked.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $vaultLockEnabled = settings('vault_lock_enabled', false);

        if (! $vaultLockEnabled) {
            return $next($request);
        }

        $vaultLock = VaultLock::where('user_id', $user->id)->first();

        if (! $vaultLock) {
            return $next($request);
        }

        if (! $request->session()->get('vault_unlocked', false)) {
            if ($request->method() === 'GET' && ! $request->routeIs('vault.lock')) {
                $request->session()->put('vault_intended_url', $request->fullUrl());
            }

            return redirect()->route('vault.lock');
        }

        return $next($request);
    }
}
