<?php

/**
 * Transaction Controller
 *
 * Handles CRUD operations for financial transactions including income,
 * expenses, and transfers with cross-currency conversion. Manages wallet
 * balance updates, goal allocations, and cached reference data.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Treasury\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Cache\CacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Categories\Models\Category;
use Modules\Media\Models\MediaFile;
use Modules\Treasury\Http\Requests\StoreTransactionRequest;
use Modules\Treasury\Http\Requests\UpdateTransactionRequest;
use Modules\Treasury\Models\Transaction;
use Modules\Treasury\Models\Wallet;
use Modules\Treasury\Services\Exchange\CurrencyConverter;

/**
 * Class TransactionController
 *
 * Handles CRUD operations for financial transactions.
 */
class TransactionController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    private const CACHE_TAG = 'treasury';

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly CurrencyConverter $currencyConverter
    ) {}

    /**
     * Display a listing of transactions.
     *
     * @param  Request  $request  The incoming request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Transaction::class);

        $query = Transaction::forUser()
            ->with(['wallet', 'destinationWallet', 'category', 'goal.category', 'attachments'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('wallet_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('wallet_id', $request->wallet_id)
                    ->orWhere('destination_wallet_id', $request->wallet_id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        $transactions = $query->paginate(25)->withQueryString();

        if ($request->filled('wallet_id')) {
            $filteredWalletId = $request->wallet_id;
            $transactions->through(function ($tx) use ($filteredWalletId) {
                $isIncomingTransfer = $tx->type === 'transfer' && $tx->destination_wallet_id === $filteredWalletId;
                $convertedAmount = null;
                if ($isIncomingTransfer && $tx->exchange_rate && $tx->exchange_rate != 1) {
                    $convertedAmount = bcmul((string) $tx->amount, (string) $tx->exchange_rate, 2);
                }

                $tx->is_incoming_transfer = $isIncomingTransfer;
                $tx->converted_amount = $convertedAmount ? (float) $convertedAmount : null;

                return $tx;
            });
        }

        $wallets = $this->getWallets();
        $categories = $this->getCategories();

        $referenceCurrency = settings('treasury_reference_currency', 'IDR');
        $exchangeRates = $this->getExchangeRates();

        return Inertia::render('Modules::Treasury/Transactions/Index', [
            'transactions' => $transactions,
            'wallets' => $wallets,
            'categories' => $categories,
            'types' => Transaction::TYPES,
            'filters' => $request->only(['wallet_id', 'type', 'category_id', 'start_date', 'end_date']),
            'exchangeRates' => $exchangeRates,
            'referenceCurrency' => $referenceCurrency,
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     *
     * @return Response
     */
    public function create(): Response
    {
        $this->authorize('create', Transaction::class);

        $wallets = $this->getWallets();
        $categories = $this->getCategories();

        return Inertia::render('Modules::Treasury/Transactions/Create', [
            'wallets' => $wallets,
            'categories' => $categories,
            'types' => Transaction::TYPES,
            'exchangeRates' => $this->getExchangeRates(),
            'referenceCurrency' => settings('treasury_reference_currency', 'IDR'),
        ]);
    }

    /**
     * Store a newly created transaction.
     *
     * @param  StoreTransactionRequest  $request  The validated request
     * @return RedirectResponse
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($validated['type'] === 'transfer' && ! empty($validated['destination_wallet_id'])) {
            $sourceWallet = Wallet::find($validated['wallet_id']);
            $destWallet = Wallet::find($validated['destination_wallet_id']);

            if ($sourceWallet && $destWallet && $sourceWallet->currency !== $destWallet->currency) {
                if (empty($validated['exchange_rate'])) {
                    $rate = $this->currencyConverter->getRate(
                        $sourceWallet->currency,
                        $destWallet->currency
                    );
                    $validated['exchange_rate'] = $rate;
                }
                $validated['original_currency'] = $sourceWallet->currency;
            }
        }

        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        if ($request->has('attachment_ids')) {
            $attachmentIds = $request->input('attachment_ids', []);
            if (! empty($attachmentIds)) {
                MediaFile::whereIn('id', $attachmentIds)
                    ->where('is_temporary', true)
                    ->update([
                        'model_type' => Transaction::class,
                        'model_id' => $transaction->id,
                        'is_temporary' => false,
                    ]);
            }
        }

        return redirect()
            ->route('treasury.transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified transaction.
     *
     * @param  Transaction  $transaction  The transaction to show
     * @return Response
     */
    public function show(Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        $transaction->load(['wallet', 'destinationWallet', 'category', 'goal.category', 'attachments']);

        return Inertia::render('Modules::Treasury/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     *
     * @param  Transaction  $transaction  The transaction to edit
     * @return Response
     */
    public function edit(Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        $transaction->load(['wallet', 'destinationWallet', 'category', 'goal.category', 'attachments']);

        $wallets = $this->getWallets();
        $categories = $this->getCategories();

        return Inertia::render('Modules::Treasury/Transactions/Edit', [
            'transaction' => $transaction,
            'wallets' => $wallets,
            'categories' => $categories,
            'types' => Transaction::TYPES,
            'exchangeRates' => $this->getExchangeRates(),
            'referenceCurrency' => settings('treasury_reference_currency', 'IDR'),
        ]);
    }

    /**
     * Update the specified transaction.
     *
     * @param  UpdateTransactionRequest  $request  The validated request
     * @param  Transaction  $transaction  The transaction to update
     * @return RedirectResponse
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validated();

        $type = $validated['type'] ?? $transaction->type;
        $walletId = $validated['wallet_id'] ?? $transaction->wallet_id;
        $destWalletId = $validated['destination_wallet_id'] ?? $transaction->destination_wallet_id;

        if ($type === 'transfer' && ! empty($destWalletId)) {
            $sourceWallet = Wallet::find($walletId);
            $destWallet = Wallet::find($destWalletId);

            if ($sourceWallet && $destWallet && $sourceWallet->currency !== $destWallet->currency) {
                if (empty($validated['exchange_rate'])) {
                    $rate = $this->currencyConverter->getRate(
                        $sourceWallet->currency,
                        $destWallet->currency
                    );
                    $validated['exchange_rate'] = $rate;
                }
                $validated['original_currency'] = $sourceWallet->currency;
            }
        }

        $transaction->update($validated);

        if ($request->has('attachment_ids')) {
            $attachmentIds = $request->input('attachment_ids', []);
            if (! empty($attachmentIds)) {
                MediaFile::whereIn('id', $attachmentIds)
                    ->where('is_temporary', true)
                    ->update([
                        'model_type' => Transaction::class,
                        'model_id' => $transaction->id,
                        'is_temporary' => false,
                    ]);
            }
        }

        if ($request->has('remove_attachment_ids')) {
            $removeIds = $request->input('remove_attachment_ids', []);
            if (! empty($removeIds)) {
                $transaction->attachments()
                    ->whereIn('id', $removeIds)
                    ->delete();
            }
        }

        return redirect()
            ->route('treasury.transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified transaction.
     *
     * @param  Transaction  $transaction  The transaction to delete
     * @return RedirectResponse
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return redirect()
            ->route('treasury.transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Get cached wallets for the authenticated user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getWallets()
    {
        return $this->cacheService->remember(
            'treasury:wallets:'.Auth::id(),
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Wallet::forUser()->active()->get(),
            self::CACHE_TAG,
            'TransactionController'
        );
    }

    /**
     * Get cached transaction categories.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCategories()
    {
        return $this->cacheService->remember(
            'treasury:categories:transaction',
            now()->addHours(self::CACHE_TTL_HOURS),
            fn () => Category::where('type', 'transaction_category')->orderBy('name')->get(),
            self::CACHE_TAG,
            'TransactionController'
        );
    }

    /**
     * Get cached exchange rates for cross-currency calculations.
     *
     * Exchange rates are cached for 1 hour since they're typically updated daily.
     *
     * @return array<string, float>
     */
    private function getExchangeRates()
    {
        return $this->cacheService->remember(
            'treasury:exchange_rates',
            now()->addHours(1),
            fn () => \Modules\Treasury\Models\ExchangeRate::pluck('rate_to_usd', 'currency_code')->toArray(),
            self::CACHE_TAG,
            'TransactionController'
        );
    }
}
