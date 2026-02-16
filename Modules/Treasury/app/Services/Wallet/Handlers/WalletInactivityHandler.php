<?php

/**
 * Wallet Inactivity Handler
 *
 * Signal handler that returns data when wallets have no transactions
 * in the past 30 days (scheduled weekly).
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Wallet\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Wallet Inactivity Handler
 *
 * Returns signal data for inactive wallets (scheduled weekly).
 */
class WalletInactivityHandler implements SignalHandlerInterface
{
    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['scheduled.weekly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'WalletInactivityHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        return $data instanceof User;
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        /** @var User $user */
        $user = $data;

        $inactiveWallets = Wallet::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereDoesntHave('transactions', function ($query) {
                $query->where('date', '>=', now()->subDays(30));
            })
            ->count();

        if ($inactiveWallets === 0) {
            return null;
        }

        Log::info('WalletInactivityHandler: Inactive wallets', [
            'user_id' => $user->id,
            'count' => $inactiveWallets,
        ]);

        return [
            'type' => 'info',
            'title' => 'Inactive Wallets Detected',
            'message' => "You have {$inactiveWallets} wallet(s) with no transactions in the past 30 days. Consider reviewing these accounts or archiving unused wallets.",
            'url' => route('treasury.wallets.index'),
            'category' => 'treasury_wallet',
        ];
    }
}
