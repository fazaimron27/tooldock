<?php

/**
 * Wallet Net Worth Handler
 *
 * Signal handler that returns data when a user reaches net worth
 * milestones across all active wallets.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services\Wallet\Handlers;

use App\Services\Registry\SignalHandlerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\User;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Services\Exchange\CurrencyConverter;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Wallet Net Worth Handler
 *
 * Returns signal data when user reaches net worth milestones.
 */
class WalletNetWorthHandler implements SignalHandlerInterface
{
    private const MILESTONES = [
        1000000,
        5000000,
        10000000,
        25000000,
        50000000,
        100000000,
        250000000,
        500000000,
        1000000000,
    ];

    public function __construct(
        private readonly CurrencyFormatter $currencyFormatter,
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /** {@inheritdoc} */
    public function getEvents(): array
    {
        return ['transaction.created', 'scheduled.monthly'];
    }

    /** {@inheritdoc} */
    public function getModule(): string
    {
        return 'Treasury';
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return 'WalletNetWorthHandler';
    }

    /** {@inheritdoc} */
    public function supports(string $event, mixed $data): bool
    {
        if ($data instanceof User) {
            return true;
        }

        return $data instanceof Transaction && $data->type === 'income';
    }

    /** {@inheritdoc} */
    public function handle(mixed $data): ?array
    {
        $user = $data instanceof User ? $data : $data->user;
        if (! $user) {
            return null;
        }

        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $netWorth = $this->calculateNetWorth($user, $referenceCurrency);

        foreach (self::MILESTONES as $milestone) {
            if ($netWorth >= $milestone) {
                $cacheKey = "wallet_net_worth_milestone_{$user->id}_{$milestone}";
                if (Cache::has($cacheKey)) {
                    continue;
                }

                Cache::forever($cacheKey, true);

                $formattedMilestone = $this->currencyFormatter->format($milestone, $referenceCurrency);
                $formattedNetWorth = $this->currencyFormatter->format($netWorth, $referenceCurrency);
                $milestoneLabel = $netWorth > $milestone ? "Passed {$formattedMilestone}" : "{$formattedMilestone} Reached";

                Log::info('WalletNetWorthHandler: Milestone reached', [
                    'user_id' => $user->id,
                    'milestone' => $milestone,
                    'actual_net_worth' => $netWorth,
                ]);

                return [
                    'type' => 'success',
                    'title' => "Net Worth Milestone: {$milestoneLabel}",
                    'message' => "Congratulations! Your total net worth is now {$formattedNetWorth}. Your financial growth is on track!",
                    'url' => route('treasury.dashboard'),
                    'category' => 'treasury_wallet',
                ];
            }
        }

        return null;
    }

    /**
     * Calculate the user's total net worth across all active wallets.
     *
     * @param  User  $user
     * @param  string  $referenceCurrency
     * @return float
     */
    private function calculateNetWorth(User $user, string $referenceCurrency): float
    {
        $wallets = Wallet::where('user_id', $user->id)->where('is_active', true)->get();
        $total = 0;

        foreach ($wallets as $wallet) {
            $balance = (float) $wallet->balance;
            if ($wallet->currency !== $referenceCurrency) {
                $balance = $this->currencyConverter->convert($balance, $wallet->currency, $referenceCurrency);
            }
            $total += $balance;
        }

        return $total;
    }
}
