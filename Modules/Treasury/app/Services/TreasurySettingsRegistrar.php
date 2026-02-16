<?php

/**
 * Treasury Settings Registrar
 *
 * Registers module settings for the Treasury financial management module.
 * Provides settings for currency preferences and financial configurations.
 *
 * Currency list sourced from: https://paytm.com/tools/currency-converter/currency/
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use App\Services\Registry\SettingsRegistry;
use Modules\Settings\Enums\SettingType;
use Modules\Treasury\Services\Support\CurrencyFormatter;

/**
 * Handles settings registration for the Treasury module.
 */
class TreasurySettingsRegistrar
{
    /**
     * Get the list of supported currencies.
     */
    private function getCurrencyOptions(): array
    {
        return CurrencyFormatter::getSettingsOptions();
    }

    /**
     * Register treasury module settings.
     */
    public function register(SettingsRegistry $registry, string $moduleName): void
    {
        $registry->registerMany($moduleName, 'treasury', [
            '_preferences' => [
                'label' => 'Currency Preferences',
                'description' => 'Configure currency and regional settings',
                'settings' => [
                    ['key' => 'treasury_reference_currency', 'value' => 'IDR', 'type' => SettingType::Select, 'label' => 'Reference Currency', 'options' => $this->getCurrencyOptions(), 'searchable' => true],
                ],
            ],
            'budget_thresholds' => [
                'label' => 'Budget Thresholds',
                'description' => 'Set alert thresholds for budget monitoring',
                'settings' => [
                    ['key' => 'treasury_budget_low_balance_threshold', 'value' => 50, 'type' => SettingType::Percentage, 'label' => 'Budget Low Balance Threshold'],
                    ['key' => 'treasury_budget_warning_threshold', 'value' => 80, 'type' => SettingType::Percentage, 'label' => 'Budget Warning Threshold'],
                    ['key' => 'treasury_budget_overbudget_threshold', 'value' => 100, 'type' => SettingType::Percentage, 'label' => 'Overbudget Threshold'],
                    ['key' => 'treasury_unbudgeted_spending_threshold', 'value' => 100000, 'type' => SettingType::Currency, 'label' => 'Unbudgeted Spending Alert Threshold'],
                    ['key' => 'treasury_rollover_debt_threshold', 'value' => 100000, 'type' => SettingType::Currency, 'label' => 'Rollover Debt Alert Threshold'],
                ],
            ],
            'wallet_thresholds' => [
                'label' => 'Wallet Thresholds',
                'description' => 'Set alert thresholds for wallet balance monitoring',
                'settings' => [
                    ['key' => 'treasury_wallet_low_balance_threshold', 'value' => 500000, 'type' => SettingType::Currency, 'label' => 'Low Balance Threshold'],
                    ['key' => 'treasury_wallet_critical_threshold', 'value' => 100000, 'type' => SettingType::Currency, 'label' => 'Critical Balance Threshold'],
                    ['key' => 'treasury_wallet_inactivity_days', 'value' => 30, 'type' => SettingType::Integer, 'label' => 'Inactivity Alert (Days)'],
                    ['key' => 'treasury_large_transaction_threshold', 'value' => 1000000, 'type' => SettingType::Currency, 'label' => 'Large Transaction Alert Threshold'],
                ],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'Choose which notifications you want to receive',
                'permission' => 'treasuries.preferences.view',
                'settings' => [
                    ['key' => 'treasury_budget_notify_enabled', 'value' => true, 'type' => SettingType::Boolean, 'label' => 'Budget Notifications', 'scope' => 'user'],
                    ['key' => 'treasury_wallet_notify_enabled', 'value' => true, 'type' => SettingType::Boolean, 'label' => 'Wallet Notifications', 'scope' => 'user'],
                    ['key' => 'treasury_goal_notify_enabled', 'value' => true, 'type' => SettingType::Boolean, 'label' => 'Goal Notifications', 'scope' => 'user'],
                    ['key' => 'treasury_transaction_notify_enabled', 'value' => true, 'type' => SettingType::Boolean, 'label' => 'Transaction Notifications', 'scope' => 'user'],
                ],
            ],
        ]);
    }
}
