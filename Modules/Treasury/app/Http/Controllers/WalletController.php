<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Models\Category;
use Modules\Treasury\Http\Requests\StoreWalletRequest;
use Modules\Treasury\Http\Requests\UpdateWalletRequest;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;

class WalletController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    private const CACHE_TAG = 'treasury';

    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Display a listing of wallets.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Wallet::class);

        $wallets = Wallet::forUser()
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        // Use centralized method for net worth calculation
        $totalBalance = Wallet::getNetWorthForUser();

        // Fetch wallet types from categories for displaying type labels with colors
        $walletTypes = $this->getWalletTypes();

        // Get net worth history for the chart (derived from transactions)
        /** @var \Modules\Treasury\Services\Transaction\TransactionStatsService $transactionStats */
        $transactionStats = app(\Modules\Treasury\Services\Transaction\TransactionStatsService::class);
        $netWorthHistory = $transactionStats->getNetWorthHistory(
            request()->user(),
            $totalBalance,
            6
        );

        return Inertia::render('Modules::Treasury/Wallets/Index', [
            'wallets' => $wallets,
            'totals' => [
                'total' => $totalBalance,
            ],
            'walletTypes' => $walletTypes,
            'netWorthHistory' => $netWorthHistory,
        ]);
    }

    /**
     * Show the form for creating a new wallet.
     */
    public function create(): Response
    {
        $this->authorize('create', Wallet::class);

        // Fetch wallet types from categories for displaying type labels with colors
        $walletTypes = $this->getWalletTypes();

        return Inertia::render('Modules::Treasury/Wallets/Create', [
            'walletTypes' => $walletTypes,
        ]);
    }

    /**
     * Store a newly created wallet.
     */
    public function store(StoreWalletRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $initialBalance = (float) ($validated['balance'] ?? 0);
        unset($validated['balance']);

        // Create wallet with 0 balance first
        $wallet = Wallet::create([
            'user_id' => $request->user()->id,
            'balance' => 0,
            ...$validated,
        ]);

        // If there's an initial balance, create an "Opening Balance" transaction
        // Our Transaction observers will automatically update the wallet balance
        if ($initialBalance > 0) {
            $initialBalanceCategory = Category::where('type', 'transaction_category')
                ->where('slug', 'initial-balance')
                ->first();

            Transaction::create([
                'user_id' => $request->user()->id,
                'wallet_id' => $wallet->id,
                'category_id' => $initialBalanceCategory?->id,
                'type' => 'income',
                'name' => 'Opening Balance',
                'amount' => $initialBalance,
                'date' => now(),
                'description' => 'Initial balance for '.$wallet->name,
            ]);
        }

        return redirect()
            ->route('treasury.wallets.index')
            ->with('success', "Wallet '{$wallet->name}' created successfully.");
    }

    /**
     * Display the specified wallet.
     */
    public function show(Wallet $wallet): Response
    {
        $this->authorize('view', $wallet);

        // Get transactions where this wallet is the source OR destination (for incoming transfers)
        $recentTransactions = Transaction::where(function ($query) use ($wallet) {
            $query->where('wallet_id', $wallet->id)
                ->orWhere('destination_wallet_id', $wallet->id);
        })
            ->with(['wallet', 'destinationWallet', 'category', 'attachments'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($tx) use ($wallet) {
                // Determine if this is an incoming transfer to this wallet
                $isIncomingTransfer = $tx->type === 'transfer' && $tx->destination_wallet_id === $wallet->id;

                // Calculate converted amount for incoming cross-currency transfers
                $convertedAmount = null;
                if ($isIncomingTransfer && $tx->exchange_rate && $tx->exchange_rate != 1) {
                    // For incoming transfers, the converted amount is: amount * exchange_rate
                    $convertedAmount = bcmul((string) $tx->amount, (string) $tx->exchange_rate, 2);
                }

                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'name' => $tx->name,
                    'amount' => (float) $tx->amount,
                    'fee' => (float) $tx->fee,
                    'converted_amount' => $convertedAmount ? (float) $convertedAmount : null,
                    'exchange_rate' => $tx->exchange_rate ? (float) $tx->exchange_rate : null,
                    'description' => $tx->description,
                    'date' => $tx->date,
                    'wallet' => $tx->wallet ? [
                        'id' => $tx->wallet->id,
                        'name' => $tx->wallet->name,
                        'currency' => $tx->wallet->currency,
                    ] : null,
                    'destination_wallet' => $tx->destinationWallet ? [
                        'id' => $tx->destinationWallet->id,
                        'name' => $tx->destinationWallet->name,
                        'currency' => $tx->destinationWallet->currency,
                    ] : null,
                    'is_incoming_transfer' => $isIncomingTransfer,
                    'category' => $tx->category ? [
                        'id' => $tx->category->id,
                        'name' => $tx->category->name,
                        'slug' => $tx->category->slug,
                        'color' => $tx->category->color,
                    ] : null,
                    'attachments' => $tx->attachments->map(fn ($a) => [
                        'id' => $a->id,
                        'filename' => $a->filename,
                    ])->toArray(),
                ];
            });

        // Fetch the wallet type info for display
        $walletType = Category::where('type', 'wallet_type')
            ->where('slug', $wallet->type)
            ->first(['name', 'slug', 'color']);

        // Calculate wallet statistics using a single query with conditional aggregation
        $stats = Transaction::query()
            ->selectRaw("
                COALESCE(SUM(CASE WHEN wallet_id = ? AND type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN wallet_id = ? AND type = 'expense' THEN amount ELSE 0 END), 0) as total_expense,
                COALESCE(SUM(CASE WHEN wallet_id = ? AND type = 'transfer' THEN amount ELSE 0 END), 0) as total_transfers_out,
                COALESCE(SUM(CASE WHEN destination_wallet_id = ? AND type = 'transfer' THEN amount ELSE 0 END), 0) as total_transfers_in,
                COUNT(CASE WHEN wallet_id = ? OR destination_wallet_id = ? THEN 1 END) as transaction_count
            ", [$wallet->id, $wallet->id, $wallet->id, $wallet->id, $wallet->id, $wallet->id])
            ->where(function ($query) use ($wallet) {
                $query->where('wallet_id', $wallet->id)
                    ->orWhere('destination_wallet_id', $wallet->id);
            })
            ->first();

        $walletStats = [
            'total_income' => (float) $stats->total_income,
            'total_expense' => (float) $stats->total_expense,
            'total_transfers_out' => (float) $stats->total_transfers_out,
            'total_transfers_in' => (float) $stats->total_transfers_in,
            'transaction_count' => (int) $stats->transaction_count,
        ];

        // Fetch goals linked to this wallet
        $goals = TreasuryGoal::where('wallet_id', $wallet->id)
            ->with('category')
            ->orderBy('is_completed')
            ->orderBy('deadline')
            ->get()
            ->map(fn ($goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'currency' => $goal->currency,
                'target_amount' => $goal->target_amount,
                'saved_amount' => $goal->saved_amount,
                'progress' => $goal->progress_percentage,
                'is_completed' => $goal->is_completed,
                'deadline' => $goal->deadline,
                'category' => $goal->category ? [
                    'name' => $goal->category->name,
                    'color' => $goal->category->color,
                ] : null,
            ]);

        return Inertia::render('Modules::Treasury/Wallets/Show', [
            'wallet' => $wallet,
            'walletType' => $walletType,
            'walletStats' => $walletStats,
            'recentTransactions' => $recentTransactions,
            'goals' => $goals,
        ]);
    }

    /**
     * Show the form for editing the specified wallet.
     */
    public function edit(Wallet $wallet): Response
    {
        $this->authorize('update', $wallet);

        $walletTypes = $this->getWalletTypes();

        return Inertia::render('Modules::Treasury/Wallets/Edit', [
            'wallet' => $wallet,
            'walletTypes' => $walletTypes,
        ]);
    }

    /**
     * Update the specified wallet.
     */
    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $wallet->update($request->validated());

        return redirect()
            ->route('treasury.wallets.index')
            ->with('success', "Wallet '{$wallet->name}' updated successfully.");
    }

    /**
     * Remove the specified wallet.
     */
    public function destroy(Wallet $wallet): RedirectResponse
    {
        $this->authorize('delete', $wallet);

        // Prevent deletion if wallet is linked to an active goal
        $activeGoal = \Modules\Treasury\Models\TreasuryGoal::where('wallet_id', $wallet->id)
            ->where('is_completed', false)
            ->first();

        if ($activeGoal) {
            return redirect()
                ->route('treasury.wallets.index')
                ->with('error', "Cannot delete wallet '{$wallet->name}' - it is linked to active goal '{$activeGoal->name}'.");
        }

        $name = $wallet->name;
        $wallet->delete();

        return redirect()
            ->route('treasury.wallets.index')
            ->with('success', "Wallet '{$name}' deleted successfully.");
    }

    /**
     * Get cached wallet types.
     */
    private function getWalletTypes()
    {
        return $this->cacheService->remember(
            'treasury:categories:wallet_type',
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Category::where('type', 'wallet_type')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'color', 'description']),
            self::CACHE_TAG,
            'WalletController'
        );
    }
}
