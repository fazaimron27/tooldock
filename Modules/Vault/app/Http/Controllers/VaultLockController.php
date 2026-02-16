<?php

/**
 * Vault Lock Controller
 *
 * Manages vault lock/unlock lifecycle, PIN setup, and lock status checks.
 * Works with VaultLockMiddleware to enforce session-based vault access control.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Vault\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Core\UserPreferenceService;
use App\Services\Registry\SignalHandlerRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Vault\Models\VaultLock;

/**
 * Class VaultLockController
 *
 * Provides endpoints for vault lock screen display, PIN-based unlock/lock,
 * PIN creation/update, and lock status polling. Dispatches signal events
 * on lock state changes for notification delivery.
 *
 * @see \Modules\Vault\Http\Middleware\VaultLockMiddleware
 * @see \Modules\Vault\Models\VaultLock
 */
class VaultLockController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  SignalHandlerRegistry  $signalRegistry  Registry for dispatching lock/unlock signals
     * @param  UserPreferenceService  $preferenceService  Service for reading user-scoped preferences
     * @return void
     */
    public function __construct(
        private readonly SignalHandlerRegistry $signalRegistry,
        private readonly UserPreferenceService $preferenceService
    ) {}

    /**
     * Show the vault lock screen.
     *
     * @return Response|RedirectResponse Inertia lock page or redirect if lock is disabled
     */
    public function show(): Response|RedirectResponse
    {
        Gate::authorize('vaults.vault.view');

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
     *
     * @param  Request  $request  The incoming HTTP request containing the PIN
     * @return RedirectResponse Redirect to intended URL or vault index
     *
     * @throws ValidationException If the provided PIN is incorrect
     */
    public function unlock(Request $request): RedirectResponse
    {
        Gate::authorize('vaults.vault.view');

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
        $request->session()->forget('vault_autolock_notified');

        $intendedUrl = $request->session()->pull('vault_intended_url', route('vault.index'));

        if (! is_string($intendedUrl) || empty($intendedUrl) || ! $this->isInternalUrl($intendedUrl)) {
            $intendedUrl = route('vault.index');
        }

        $user = Auth::user();
        $actionUrl = $user->can('vaults.vault.view') ? route('vault.index') : null;

        $this->signalRegistry->dispatch('vault.unlocked', [
            'user' => $user,
        ]);

        return redirect($intendedUrl);
    }

    /**
     * Lock the vault manually.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return RedirectResponse Redirect to the vault lock screen
     */
    public function lock(Request $request): RedirectResponse
    {
        Gate::authorize('vaults.vault.view');

        if (! $this->isVaultLockEnabled()) {
            return redirect()->route('vault.index')
                ->with('warning', 'Vault lock is currently disabled.');
        }

        $request->session()->forget('vault_unlocked');
        $request->session()->forget('vault_unlocked_at');

        $vaultLock = $this->getVaultLock();
        if ($vaultLock) {
            $user = Auth::user();
            $actionUrl = $user->can('vaults.vault.view') ? route('vault.lock') : null;

            $this->signalRegistry->dispatch('vault.locked', [
                'user' => $user,
            ]);
        }

        return redirect()->route('vault.lock');
    }

    /**
     * Set or update the vault PIN.
     *
     * @param  Request  $request  The incoming HTTP request containing the new PIN
     * @return RedirectResponse Redirect to vault index with success message
     */
    public function setPin(Request $request): RedirectResponse
    {
        Gate::authorize('vaults.vault.view');

        if (! $this->isVaultLockEnabled()) {
            return redirect()->route('vault.index')
                ->with('warning', 'Vault lock is currently disabled. Please enable it in settings first.');
        }

        $request->validate([
            'pin' => ['required', 'string', 'min:4', 'max:20', 'confirmed'],
        ]);

        $user = Auth::user();
        $existingLock = VaultLock::where('user_id', $user->id)->first();
        $isUpdate = $existingLock !== null;

        VaultLock::updateOrCreate(
            ['user_id' => $user->id],
            ['pin_hash' => Hash::make($request->pin)]
        );

        $request->session()->put('vault_unlocked', true);
        $request->session()->put('vault_unlocked_at', now()->timestamp);
        $request->session()->forget('vault_autolock_notified');

        $this->signalRegistry->dispatch('vault.pin.changed', [
            'user' => $user,
            'is_update' => $isUpdate,
        ]);

        return redirect()->route('vault.index')
            ->with('success', 'Vault PIN set successfully.');
    }

    /**
     * Check if vault is currently unlocked (for frontend).
     *
     * @param  Request  $request  The incoming HTTP request
     * @return JsonResponse JSON response with unlock status and timestamp
     */
    public function status(Request $request): JsonResponse
    {
        Gate::authorize('vaults.vault.view');

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
     *
     * Reads the user-scoped preference, falling back to the global setting.
     *
     * @return bool True if vault lock is enabled for the current user
     */
    private function isVaultLockEnabled(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return settings('vault_lock_enabled', false);
        }

        return $this->preferenceService->get($user, 'vault_lock_enabled', false);
    }

    /**
     * Get the vault lock record for the authenticated user.
     *
     * @return VaultLock|null The vault lock record, or null if none exists
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
     * @param  string  $url  The URL to validate
     * @return bool True if the URL is internal to the application
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
