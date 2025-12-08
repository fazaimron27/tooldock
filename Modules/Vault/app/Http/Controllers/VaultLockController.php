<?php

namespace Modules\Vault\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Vault\Models\VaultLock;

class VaultLockController extends Controller
{
    /**
     * Show the vault lock screen.
     */
    public function show(): Response|RedirectResponse
    {
        if (! $this->isVaultLockEnabled()) {
            return redirect()->route('vault.index')
                ->with('warning', 'Vault lock is currently disabled.');
        }

        $vaultLock = $this->getVaultLock();

        return Inertia::render('Modules::Vault/Lock', [
            'hasLock' => (bool) $vaultLock,
        ]);
    }

    /**
     * Unlock the vault with PIN/password.
     */
    public function unlock(Request $request): RedirectResponse
    {
        if (! $this->isVaultLockEnabled()) {
            return redirect()->route('vault.index')
                ->with('warning', 'Vault lock is currently disabled.');
        }

        $vaultLock = $this->getVaultLock();

        if (! $vaultLock) {
            return redirect()->route('vault.index');
        }

        $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:20'],
        ]);

        if (! Hash::check($request->pin, $vaultLock->pin_hash)) {
            throw ValidationException::withMessages([
                'pin' => ['The provided PIN is incorrect.'],
            ]);
        }

        $request->session()->put('vault_unlocked', true);
        $request->session()->put('vault_unlocked_at', now()->timestamp);

        $intendedUrl = $request->session()->pull('vault_intended_url', route('vault.index'));

        if (! is_string($intendedUrl) || empty($intendedUrl) || ! $this->isInternalUrl($intendedUrl)) {
            $intendedUrl = route('vault.index');
        }

        return redirect($intendedUrl);
    }

    /**
     * Lock the vault manually.
     */
    public function lock(Request $request): RedirectResponse
    {
        if (! $this->isVaultLockEnabled()) {
            return redirect()->route('vault.index')
                ->with('warning', 'Vault lock is currently disabled.');
        }

        $request->session()->forget('vault_unlocked');
        $request->session()->forget('vault_unlocked_at');

        return redirect()->route('vault.lock');
    }

    /**
     * Set or update the vault PIN.
     */
    public function setPin(Request $request): RedirectResponse
    {
        if (! $this->isVaultLockEnabled()) {
            return redirect()->route('vault.index')
                ->with('warning', 'Vault lock is currently disabled. Please enable it in settings first.');
        }

        $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ]);

        $user = Auth::user();

        VaultLock::updateOrCreate(
            ['user_id' => $user->id],
            ['pin_hash' => Hash::make($request->pin)]
        );

        $request->session()->put('vault_unlocked', true);
        $request->session()->put('vault_unlocked_at', now()->timestamp);

        return redirect()->route('vault.index')
            ->with('success', 'Vault PIN set successfully.');
    }

    /**
     * Check if vault is currently unlocked (for frontend).
     */
    public function status(Request $request): JsonResponse
    {
        if (! $this->isVaultLockEnabled()) {
            return response()->json([
                'unlocked' => true,
                'unlocked_at' => null,
            ]);
        }

        $unlocked = (bool) $request->session()->get('vault_unlocked', false);
        $unlockedAt = $request->session()->get('vault_unlocked_at');

        return response()->json([
            'unlocked' => $unlocked,
            'unlocked_at' => $unlockedAt,
        ]);
    }

    /**
     * Check if vault lock feature is enabled.
     */
    private function isVaultLockEnabled(): bool
    {
        return settings('vault_lock_enabled', false);
    }

    /**
     * Get the vault lock record for the authenticated user.
     */
    private function getVaultLock(): ?VaultLock
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        return VaultLock::where('user_id', $userId)->first();
    }

    /**
     * Check if a URL is internal to prevent open redirect vulnerabilities.
     *
     * @param  string  $url
     * @return bool
     */
    private function isInternalUrl(string $url): bool
    {
        try {
            $parsed = parse_url($url);

            if ($parsed === false) {
                return false;
            }

            if (! isset($parsed['host'])) {
                return true;
            }

            $appUrlString = config('app.url');
            if (! $appUrlString || ! is_string($appUrlString)) {
                return false;
            }

            $appUrl = parse_url($appUrlString);

            if ($appUrl === false) {
                return false;
            }

            return isset($appUrl['host']) && $parsed['host'] === $appUrl['host'];
        } catch (\Exception $e) {
            return false;
        }
    }
}
