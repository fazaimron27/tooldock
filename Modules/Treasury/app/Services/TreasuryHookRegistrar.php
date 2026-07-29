<?php

/**
 * Treasury Hook Registrar
 *
 * Registers Treasury module models as hookable outbound webhook triggers.
 * Lives in Treasury so that Hook remains fully optional — Treasury is the
 * one that knows it wants to integrate with Hook, not the other way around.
 *
 * Loaded by TreasuryServiceProvider only when Hook is installed and active.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Services;

use App\Services\Registry\HookEventRegistryInterface;
use Carbon\Carbon;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\Wallet;

/**
 * Class TreasuryHookRegistrar
 *
 * Registers Treasury model lifecycle triggers with the Hook event registry.
 * Called from TreasuryServiceProvider, guarded by app()->bound() so it is
 * silently skipped when Hook is uninstalled.
 */
class TreasuryHookRegistrar
{
    /**
     * Register Treasury model triggers with the Hook event registry.
     *
     * @param  HookEventRegistryInterface  $registry
     * @return void
     */
    public function register(HookEventRegistryInterface $registry): void
    {
        $schema = ['id', 'type', 'name', 'amount', 'currency', 'description', 'date'];

        $formatter = function (Transaction $tx): array {
            /** @var Wallet|null $wallet */
            $wallet = $tx->wallet;

            return [
                'id' => $tx->id,
                'type' => ucfirst($tx->type),
                'name' => $tx->name,
                'amount' => number_format((float) $tx->amount, 0, '.', ','),
                'currency' => $tx->original_currency
                    ?? $wallet?->currency
                    ?? 'IDR',
                'description' => $tx->description ?? '-',
                'date' => $tx->date instanceof Carbon
                    ? $tx->date->format('d M Y')
                    : date('d M Y', strtotime((string) $tx->date)),
            ];
        };

        $registry->register(
            key: 'treasury.transaction_created',
            label: 'Treasury: Transaction Created',
            modelClass: Transaction::class,
            on: 'created',
            payloadSchema: $schema,
            formatter: $formatter,
        );

        $registry->register(
            key: 'treasury.transaction_updated',
            label: 'Treasury: Transaction Updated',
            modelClass: Transaction::class,
            on: 'updated',
            payloadSchema: $schema,
            formatter: $formatter,
        );

        $registry->register(
            key: 'treasury.transaction_deleted',
            label: 'Treasury: Transaction Deleted',
            modelClass: Transaction::class,
            on: 'deleted',
            payloadSchema: ['id', 'type', 'name', 'amount', 'date'],
            formatter: function (Transaction $tx): array {
                return [
                    'id' => $tx->id,
                    'type' => ucfirst($tx->type),
                    'name' => $tx->name,
                    'amount' => number_format((float) $tx->amount, 0, '.', ','),
                    'date' => $tx->date instanceof Carbon
                        ? $tx->date->format('d M Y')
                        : date('d M Y', strtotime((string) $tx->date)),
                ];
            },
        );
    }
}
