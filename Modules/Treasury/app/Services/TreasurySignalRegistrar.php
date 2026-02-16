<?php

/**
 * Treasury Signal Registrar
 *
 * Registers all Treasury module signal handlers with the central
 * SignalHandlerRegistry, organized by category (transaction, budget, wallet, goal).
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use App\Services\Registry\SignalHandlerInterface;
use App\Services\Registry\SignalHandlerRegistry;
use Modules\Treasury\Services\Budget\Handlers\BudgetRecoveryHandler;
use Modules\Treasury\Services\Budget\Handlers\BudgetRolloverHandler;
use Modules\Treasury\Services\Budget\Handlers\BudgetSummaryHandler;
use Modules\Treasury\Services\Budget\Handlers\BudgetThresholdHandler;
use Modules\Treasury\Services\Budget\Handlers\BudgetUnbudgetedHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalCompletedHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalDeadlineHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalMilestoneHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalOverdueHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalStagnationHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalSummaryHandler;
use Modules\Treasury\Services\Goal\Handlers\GoalVelocityHandler;
use Modules\Treasury\Services\Settings\ReferenceCurrencyChangedHandler;
use Modules\Treasury\Services\Transaction\Handlers\DailyTransactionHandler;
use Modules\Treasury\Services\Transaction\Handlers\FirstTransactionHandler;
use Modules\Treasury\Services\Transaction\Handlers\IncomeStreakHandler;
use Modules\Treasury\Services\Transaction\Handlers\LargeTransactionHandler;
use Modules\Treasury\Services\Transaction\Handlers\RecurringTransactionHandler;
use Modules\Treasury\Services\Transaction\Handlers\SpendingPatternHandler;
use Modules\Treasury\Services\Transaction\Handlers\TransactionMilestoneHandler;
use Modules\Treasury\Services\Transaction\Handlers\WeeklyTransactionHandler;
use Modules\Treasury\Services\Wallet\Handlers\WalletBalanceHandler;
use Modules\Treasury\Services\Wallet\Handlers\WalletInactivityHandler;
use Modules\Treasury\Services\Wallet\Handlers\WalletNetWorthHandler;
use Modules\Treasury\Services\Wallet\Handlers\WalletRecoveryHandler;

/**
 * Treasury Signal Registrar
 *
 * Registers all Treasury module signal handlers with the central SignalHandlerRegistry.
 * This class is called from the TreasuryServiceProvider during application boot.
 */
class TreasurySignalRegistrar
{
    private const MODULE_NAME = 'Treasury';

    /**
     * Signal handler class names organized by category.
     *
     * @var array<string, array<class-string<SignalHandlerInterface>>>
     */
    private const HANDLERS = [
        // Transaction signals
        'transaction' => [
            LargeTransactionHandler::class,
            IncomeStreakHandler::class,
            SpendingPatternHandler::class,
            FirstTransactionHandler::class,
            TransactionMilestoneHandler::class,
            DailyTransactionHandler::class,
            WeeklyTransactionHandler::class,
            RecurringTransactionHandler::class,
        ],

        // Budget signals
        'budget' => [
            BudgetThresholdHandler::class,
            BudgetUnbudgetedHandler::class,
            BudgetRolloverHandler::class,
            BudgetRecoveryHandler::class,
            BudgetSummaryHandler::class,
        ],

        // Wallet signals
        'wallet' => [
            WalletBalanceHandler::class,
            WalletInactivityHandler::class,
            WalletNetWorthHandler::class,
            WalletRecoveryHandler::class,
        ],

        // Goal signals
        'goal' => [
            GoalCompletedHandler::class,
            GoalMilestoneHandler::class,
            GoalDeadlineHandler::class,
            GoalVelocityHandler::class,
            GoalStagnationHandler::class,
            GoalOverdueHandler::class,
            GoalSummaryHandler::class,
        ],

        // Settings signals
        'settings' => [
            ReferenceCurrencyChangedHandler::class,
        ],
    ];

    /**
     * Register all Treasury signal handler classes with the registry.
     *
     * @param  SignalHandlerRegistry  $registry
     * @return void
     */
    public function register(SignalHandlerRegistry $registry): void
    {
        foreach (self::HANDLERS as $category => $handlerClasses) {
            foreach ($handlerClasses as $handlerClass) {
                $registry->register(self::MODULE_NAME, $handlerClass);
            }
        }
    }

    /**
     * Get all handler classes by category.
     *
     * @return array<string, array<class-string<SignalHandlerInterface>>>
     */
    public static function getHandlersByCategory(): array
    {
        return self::HANDLERS;
    }

    /**
     * Get all handler class names as a flat array.
     *
     * @return array<class-string<SignalHandlerInterface>>
     */
    public static function getAllHandlerClasses(): array
    {
        return array_merge(...array_values(self::HANDLERS));
    }

    /**
     * Get the count of registered handlers.
     *
     * @return int
     */
    public static function getHandlerCount(): int
    {
        return count(self::getAllHandlerClasses());
    }
}
