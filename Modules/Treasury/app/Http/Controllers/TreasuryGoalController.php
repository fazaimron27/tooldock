<?php

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Models\Category;
use Modules\Treasury\Http\Requests\AllocateGoalRequest;
use Modules\Treasury\Http\Requests\StoreGoalRequest;
use Modules\Treasury\Http\Requests\UpdateGoalRequest;
use Modules\Treasury\Models\ExchangeRate;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\TreasuryGoal;
use Modules\Treasury\Models\Wallet;

class TreasuryGoalController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    private const CACHE_TAG = 'treasury';

    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Display a listing of goals.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', TreasuryGoal::class);

        $goals = TreasuryGoal::forUser()
            ->with(['wallet', 'category'])
            ->orderBy('is_completed')
            ->orderBy('deadline')
            ->get()
            ->map(fn ($goal) => [
                ...$goal->toArray(),
                'progress' => $goal->progress_percentage,
                'remaining' => $goal->remaining_amount,
                'is_overdue' => $goal->is_overdue,
            ]);

        $wallets = Wallet::forUser()->where('is_active', true)->get(['id', 'name', 'type']);

        return Inertia::render('Modules::Treasury/Goals/Index', [
            'goals' => $goals,
            'wallets' => $wallets,
        ]);
    }

    /**
     * Show the form for creating a new goal.
     */
    public function create(): Response
    {
        $this->authorize('create', TreasuryGoal::class);

        $savingsWallets = $this->getSavingsWallets();
        $categories = $this->getCategories();

        return Inertia::render('Modules::Treasury/Goals/Create', [
            'wallets' => $savingsWallets,
            'categories' => $categories,
            'hasSavingsWallet' => $savingsWallets->isNotEmpty(),
        ]);
    }

    /**
     * Store a newly created goal.
     */
    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Currency is always derived from the linked savings wallet
        $wallet = Wallet::find($validated['wallet_id']);
        $validated['currency'] = $wallet->currency;

        $goal = TreasuryGoal::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return redirect()
            ->route('treasury.goals.index')
            ->with('success', "Goal '{$goal->name}' created successfully.");
    }

    /**
     * Display the specified goal.
     */
    public function show(TreasuryGoal $goal): Response
    {
        $this->authorize('view', $goal);

        $goal->load(['wallet', 'category']);

        // Paginate transactions to avoid loading all allocations into memory
        $transactions = $goal->transactions()
            ->with('wallet')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $wallets = $this->getWallets();

        // Get cached exchange rates for cross-currency allocation display
        $exchangeRates = $this->getExchangeRates();

        return Inertia::render('Modules::Treasury/Goals/Show', [
            'goal' => [
                ...$goal->toArray(),
                'progress' => $goal->progress_percentage,
                'remaining' => $goal->remaining_amount,
                'is_overdue' => $goal->is_overdue,
            ],
            'transactions' => $transactions,
            'wallets' => $wallets,
            'exchangeRates' => $exchangeRates,
        ]);
    }

    /**
     * Show the form for editing the specified goal.
     */
    public function edit(TreasuryGoal $goal): Response
    {
        $this->authorize('update', $goal);

        $goal->load(['wallet', 'category']);
        $savingsWallets = $this->getSavingsWallets();
        $categories = $this->getCategories();

        return Inertia::render('Modules::Treasury/Goals/Edit', [
            'goal' => $goal,
            'wallets' => $savingsWallets,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified goal.
     */
    public function update(UpdateGoalRequest $request, TreasuryGoal $goal): RedirectResponse
    {
        $validated = $request->validated();

        // Currency is always derived from the linked savings wallet
        if (isset($validated['wallet_id']) && $validated['wallet_id'] !== $goal->wallet_id) {
            $wallet = Wallet::find($validated['wallet_id']);
            $validated['currency'] = $wallet->currency;
        }

        $goal->update($validated);

        return redirect()
            ->route('treasury.goals.index')
            ->with('success', "Goal '{$goal->name}' updated successfully.");
    }

    /**
     * Remove the specified goal.
     */
    public function destroy(TreasuryGoal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);

        $name = $goal->name;
        $goal->delete();

        return redirect()
            ->route('treasury.goals.index')
            ->with('success', "Goal '{$name}' deleted successfully.");
    }

    /**
     * Allocate funds from a wallet to the goal.
     */
    public function allocate(AllocateGoalRequest $request, TreasuryGoal $goal): RedirectResponse
    {
        // Get the 'Goal Allocation' category for proper categorization
        $goalAllocationCategory = \Modules\Categories\Models\Category::where('slug', 'goal-allocation')
            ->where('type', 'transaction_category')
            ->first();

        if (! $goalAllocationCategory) {
            return redirect()
                ->back()
                ->with('error', 'Goal allocation category not found. Please contact administrator.');
        }

        $sourceWalletId = $request->validated('wallet_id');
        $amount = $request->validated('amount');
        $description = $request->validated('description') ?? "Transfer to goal: {$goal->name}";

        // Get source and destination wallets
        $sourceWallet = Wallet::find($sourceWalletId);
        $destinationWallet = Wallet::find($goal->wallet_id);

        // Calculate exchange rate for cross-currency transfers
        $exchangeRate = null;
        $originalCurrency = null;
        if ($sourceWallet->currency !== $destinationWallet->currency) {
            $exchangeRates = $this->getExchangeRates();
            $sourceRate = $exchangeRates[$sourceWallet->currency] ?? 1;
            $destRate = $exchangeRates[$destinationWallet->currency] ?? 1;
            $exchangeRate = $destRate / $sourceRate;
            $originalCurrency = $sourceWallet->currency;
        }

        // Create a transfer transaction from source wallet to goal's savings wallet
        Transaction::create([
            'user_id' => $request->user()->id,
            'wallet_id' => $sourceWalletId,
            'destination_wallet_id' => $goal->wallet_id,
            'goal_id' => $goal->id,
            'category_id' => $goalAllocationCategory->id,
            'type' => 'transfer',
            'name' => "Goal: {$goal->name}",
            'amount' => $amount,
            'exchange_rate' => $exchangeRate,
            'original_currency' => $originalCurrency,
            'description' => $description,
            'date' => now(),
        ]);

        return redirect()
            ->route('treasury.goals.show', $goal)
            ->with('success', 'Funds transferred to savings wallet successfully.');
    }

    /**
     * Get cached wallets for the authenticated user.
     */
    private function getWallets()
    {
        return $this->cacheService->remember(
            'treasury:wallets:'.Auth::id(),
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Wallet::forUser()->active()->get(),
            self::CACHE_TAG,
            'TreasuryGoalController'
        );
    }

    /**
     * Get cached goal categories.
     */
    private function getCategories()
    {
        return $this->cacheService->remember(
            'treasury:categories:goal',
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Category::byType('goal')->get(),
            self::CACHE_TAG,
            'TreasuryGoalController'
        );
    }

    /**
     * Get savings wallets for the authenticated user (for goal linking).
     */
    private function getSavingsWallets()
    {
        return Wallet::forUser()
            ->active()
            ->where('type', 'savings')
            ->get();
    }

    /**
     * Get cached exchange rates for cross-currency calculations.
     *
     * Exchange rates are cached for 1 hour since they're typically updated daily.
     */
    private function getExchangeRates(): array
    {
        return $this->cacheService->remember(
            'treasury:exchange_rates',
            now()->addHours(1),
            fn () => ExchangeRate::pluck('rate_to_usd', 'currency_code')->toArray(),
            self::CACHE_TAG,
            'TreasuryGoalController'
        );
    }
}
