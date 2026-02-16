/**
 * Transactions Index page - Enhanced with modern UI/UX
 * Features: Date grouping, premium styling, smooth animations, better visual hierarchy
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { formatDate } from '@/Utils/format';
import { cn } from '@/Utils/utils';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import TransactionItem, { getCategoryIcon, typeConfig } from '@Treasury/Components/TransactionItem';
import { getGoalIcon } from '@Treasury/Utils/goalIcons';
import { Link, router } from '@inertiajs/react';
import {
  ArrowDownCircle,
  ArrowRightCircle,
  ArrowUpCircle,
  Calendar,
  ChevronLeft,
  ChevronRight,
  Eye,
  Filter,
  MoreVertical,
  Paperclip,
  Pencil,
  Plus,
  Target,
  Trash2,
  X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

function groupTransactionsByDate(
  transactions,
  exchangeRates,
  referenceCurrency,
  isWalletFiltered = false
) {
  if (!transactions?.length) return [];

  const groups = {};
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);

  transactions.forEach((tx) => {
    const txDate = new Date(tx.date);
    const dateKey = txDate.toISOString().split('T')[0];

    const txDateOnly = new Date(txDate.getFullYear(), txDate.getMonth(), txDate.getDate());
    const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const yesterdayOnly = new Date(
      yesterday.getFullYear(),
      yesterday.getMonth(),
      yesterday.getDate()
    );

    let dateLabel;
    if (txDateOnly.getTime() === todayOnly.getTime()) {
      dateLabel = 'Today';
    } else if (txDateOnly.getTime() === yesterdayOnly.getTime()) {
      dateLabel = 'Yesterday';
    } else {
      dateLabel = formatDate(txDateOnly, 'long');
    }

    if (!groups[dateKey]) {
      groups[dateKey] = {
        date: dateKey,
        label: dateLabel,
        transactions: [],
        totalIncome: 0,
        totalExpense: 0,
      };
    }

    groups[dateKey].transactions.push(tx);

    if (tx.is_incoming_transfer) {
      const amount = tx.converted_amount ? tx.converted_amount : parseFloat(tx.amount);
      const currency = tx.destination_wallet?.currency || tx.wallet?.currency || referenceCurrency;
      const convertedAmount = convertCurrency(amount, currency, referenceCurrency, exchangeRates);
      groups[dateKey].totalIncome += convertedAmount;
    } else if (tx.type === 'transfer' && isWalletFiltered && !tx.is_incoming_transfer) {
      const amount = parseFloat(tx.amount);
      const walletCurrency = tx.wallet?.currency || referenceCurrency;
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      groups[dateKey].totalExpense += convertedAmount;
    } else if (tx.type === 'income') {
      const amount = parseFloat(tx.amount);
      const walletCurrency = tx.wallet?.currency || referenceCurrency;
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      groups[dateKey].totalIncome += convertedAmount;
    } else if (tx.type === 'expense') {
      const amount = parseFloat(tx.amount);
      const walletCurrency = tx.wallet?.currency || referenceCurrency;
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      groups[dateKey].totalExpense += convertedAmount;
    }
  });

  return Object.values(groups).sort((a, b) => new Date(b.date) - new Date(a.date));
}

function convertCurrency(amount, fromCurrency, toCurrency, exchangeRates) {
  if (!exchangeRates || fromCurrency === toCurrency) return amount;

  const fromRate = exchangeRates[fromCurrency];
  const toRate = exchangeRates[toCurrency];

  if (!fromRate || !toRate) return amount;

  const amountInUsd = amount / fromRate;
  return amountInUsd * toRate;
}

function calculateSummary(
  transactions,
  exchangeRates,
  referenceCurrency,
  isWalletFiltered = false
) {
  if (!transactions?.length) {
    return { totalIncome: 0, totalExpense: 0, totalSavings: 0, netFlow: 0, transactionCount: 0 };
  }

  let totalIncome = 0;
  let totalExpense = 0;
  let totalSavings = 0;

  transactions.forEach((tx) => {
    const walletCurrency = tx.wallet?.currency || referenceCurrency;

    if (tx.is_incoming_transfer && isWalletFiltered) {
      const amount = tx.converted_amount ? tx.converted_amount : parseFloat(tx.amount);
      const currency = tx.destination_wallet?.currency || walletCurrency;
      const convertedAmount = convertCurrency(amount, currency, referenceCurrency, exchangeRates);
      totalIncome += convertedAmount;
    } else if (tx.type === 'income') {
      const amount = parseFloat(tx.amount);
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      totalIncome += convertedAmount;
    } else if (tx.type === 'expense') {
      const amount = parseFloat(tx.amount);
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      totalExpense += convertedAmount;
    } else if (tx.type === 'transfer' && tx.goal) {
      const amount = parseFloat(tx.amount);
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      totalSavings += convertedAmount;
    } else if (tx.type === 'transfer' && isWalletFiltered && !tx.is_incoming_transfer && !tx.goal) {
      const amount = parseFloat(tx.amount);
      const convertedAmount = convertCurrency(
        amount,
        walletCurrency,
        referenceCurrency,
        exchangeRates
      );
      totalExpense += convertedAmount;
    }
  });

  return {
    totalIncome,
    totalExpense,
    totalSavings,
    netFlow: totalIncome - totalExpense,
    transactionCount: transactions.length,
  };
}

function TransactionRow({ tx, onDelete }) {
  const { formatCurrency } = useAppearance();

  const isTransfer = tx.type === 'transfer';
  const isIncomingTransfer = tx.is_incoming_transfer;
  const isGoalAllocation = !!tx.goal;

  const config = isIncomingTransfer
    ? { ...typeConfig.income, prefix: '+' }
    : typeConfig[tx.type] || typeConfig.expense;
  const Icon = config.icon;

  let CategoryIcon;
  let categoryColor;

  if (isGoalAllocation) {
    CategoryIcon = getGoalIcon(tx.goal?.category?.slug);
    categoryColor = tx.goal?.category?.color || '#8b5cf6';
  } else if (isTransfer) {
    CategoryIcon = config.icon;
    categoryColor = '#3b82f6';
  } else {
    CategoryIcon = getCategoryIcon(tx.category?.slug);
    categoryColor = tx.category?.color || '#6b7280';
  }

  const hasAttachments = tx.attachments && tx.attachments.length > 0;

  const displayName = isTransfer
    ? tx.name || 'Transfer'
    : tx.name || tx.description || tx.category?.name || 'Transaction';

  const handleRowClick = (e) => {
    if (e.target.closest('[data-dropdown]')) return;
    router.visit(route('treasury.transactions.show', tx.id));
  };

  return (
    <div
      className="group relative flex items-center justify-between p-4 transition-all duration-200 hover:bg-muted/40 cursor-pointer"
      onClick={handleRowClick}
    >
      <div className="flex items-center gap-4 min-w-0 flex-1">
        {/* Category icon with type indicator */}
        <div className="relative">
          <div
            className="w-12 h-12 rounded-xl flex items-center justify-center transition-all duration-200"
            style={{
              backgroundColor: `${categoryColor}15`,
              boxShadow: `0 0 0 1px ${categoryColor}15`,
            }}
          >
            <CategoryIcon className="w-5 h-5" style={{ color: categoryColor }} />
          </div>
          {/* Type indicator badge - show Target for goal allocations */}
          <div
            className={cn(
              'absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center border-2 border-background',
              isGoalAllocation
                ? 'bg-violet-500'
                : tx.type === 'income'
                  ? 'bg-emerald-500'
                  : tx.type === 'expense'
                    ? 'bg-rose-500'
                    : 'bg-blue-500'
            )}
          >
            {isGoalAllocation ? (
              <Target className="w-3 h-3 text-white" />
            ) : (
              <Icon className="w-3 h-3 text-white" />
            )}
          </div>
        </div>

        {/* Transaction details */}
        <div className="min-w-0 flex-1">
          <p className="font-semibold text-[15px] text-foreground truncate leading-tight mb-1">
            {displayName}
          </p>
          <div className="flex items-center gap-2 text-xs text-muted-foreground">
            <span className="flex items-center gap-1">
              <span
                className="w-2 h-2 rounded-full shrink-0"
                style={{ backgroundColor: categoryColor }}
              />
              {isTransfer && tx.destination_wallet ? (
                <span className="truncate max-w-[180px] flex items-center gap-1">
                  <span>{tx.wallet?.name}</span>
                  <span className="text-blue-500">→</span>
                  <span>{tx.destination_wallet?.name}</span>
                </span>
              ) : (
                <span className="truncate max-w-[100px]">{tx.wallet?.name}</span>
              )}
            </span>
            {/* Goal badge for goal allocations */}
            {isGoalAllocation && (
              <>
                <span className="opacity-40 hidden sm:inline">•</span>
                <span
                  className="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium truncate max-w-[120px]"
                  style={{
                    backgroundColor: `${categoryColor}15`,
                    color: categoryColor,
                  }}
                >
                  <Target className="w-2.5 h-2.5" />
                  {tx.goal.name}
                </span>
              </>
            )}
            {/* Category badge for regular transactions */}
            {!isGoalAllocation && tx.category && (
              <>
                <span className="opacity-40 hidden sm:inline">•</span>
                <span
                  className="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium truncate max-w-[120px]"
                  style={{
                    backgroundColor: `${categoryColor}15`,
                    color: categoryColor,
                  }}
                >
                  {tx.category.name}
                </span>
              </>
            )}
            {hasAttachments && (
              <>
                <span className="opacity-40">•</span>
                <span
                  className="flex items-center gap-0.5 text-muted-foreground"
                  title={`${tx.attachments.length} attachment(s)`}
                >
                  <Paperclip className="w-3 h-3" />
                  <span className="text-[10px]">{tx.attachments.length}</span>
                </span>
              </>
            )}
          </div>
        </div>
      </div>

      {/* Amount and actions - Responsive layout to prevent overlap */}
      <div className="flex flex-col items-end shrink-0 pl-2">
        {/* For incoming cross-currency transfers, show the converted amount */}
        {isIncomingTransfer && tx.converted_amount ? (
          <p
            className={cn(
              'font-bold text-sm md:text-lg tabular-nums tracking-tight',
              config.amountClass
            )}
          >
            {config.prefix}
            {formatCurrency(tx.converted_amount, tx.destination_wallet?.currency)}
            <span className="text-xs text-muted-foreground font-normal ml-1">
              ({formatCurrency(tx.amount, tx.wallet?.currency)})
            </span>
          </p>
        ) : (
          <p
            className={cn(
              'font-bold text-sm md:text-lg tabular-nums tracking-tight',
              config.amountClass
            )}
          >
            {config.prefix}
            {formatCurrency(tx.amount, tx.wallet?.currency)}
          </p>
        )}
        {parseFloat(tx.fee || 0) > 0 && (
          <p className="text-xs text-muted-foreground">
            +Fee: {formatCurrency(tx.fee, tx.wallet?.currency)}
          </p>
        )}
        <p className="text-[10px] text-muted-foreground mt-0.5 md:hidden">
          {formatDate(tx.date, 'short')}
        </p>
      </div>

      <div className="flex items-center gap-2 shrink-0 ml-1">
        <p
          className={cn(
            'hidden md:block font-bold text-lg tabular-nums tracking-tight mr-2',
            config.amountClass
          )}
        >
          {/* Amount already shown in responsive div above, this handles desktop layout consistency if needed */}
        </p>

        {/* Actions dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild data-dropdown>
            <Button
              variant="ghost"
              size="icon"
              className="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity"
              onClick={(e) => e.stopPropagation()}
            >
              <MoreVertical className="w-4 h-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-40" data-dropdown>
            <DropdownMenuItem asChild>
              <Link
                href={route('treasury.transactions.show', tx.id)}
                className="flex items-center gap-2"
              >
                <Eye className="w-4 h-4" />
                View Details
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link
                href={route('treasury.transactions.edit', tx.id)}
                className="flex items-center gap-2"
              >
                <Pencil className="w-4 h-4" />
                Edit
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem
              className="text-destructive flex items-center gap-2"
              onClick={(e) => {
                e.stopPropagation();
                onDelete(tx);
              }}
            >
              <Trash2 className="w-4 h-4" />
              Delete
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  );
}

export default function Index({
  transactions,
  wallets,
  categories,
  types,
  filters,
  exchangeRates,
  referenceCurrency,
}) {
  const { formatCurrency } = useAppearance();
  const [showFilters, setShowFilters] = useState(false);
  const [deleteTransaction, setDeleteTransaction] = useState(null);
  const [filterData, setFilterData] = useState({
    wallet_id: filters?.wallet_id || 'all',
    type: filters?.type || 'all',
    category_id: filters?.category_id || 'all',
    start_date: filters?.start_date || '',
    end_date: filters?.end_date || '',
  });

  const groupedTransactions = useMemo(
    () =>
      groupTransactionsByDate(
        transactions?.data || [],
        exchangeRates,
        referenceCurrency,
        !!filters?.wallet_id
      ),
    [transactions?.data, exchangeRates, referenceCurrency, filters?.wallet_id]
  );

  const summary = useMemo(
    () =>
      calculateSummary(
        transactions?.data || [],
        exchangeRates,
        referenceCurrency,
        !!filters?.wallet_id
      ),
    [transactions?.data, exchangeRates, referenceCurrency, filters?.wallet_id]
  );

  const hasActiveFilters = Object.entries(filterData).some(([_key, v]) => v !== '' && v !== 'all');
  const applyFilters = () => {
    const serverFilters = Object.fromEntries(
      Object.entries(filterData)
        .filter(([_, v]) => v !== '' && v !== 'all')
        .map(([k, v]) => [k, v])
    );
    router.get(route('treasury.transactions.index'), serverFilters, { preserveState: true });
  };

  const clearFilters = () => {
    setFilterData({
      wallet_id: 'all',
      type: 'all',
      category_id: 'all',
      start_date: '',
      end_date: '',
    });
    router.get(route('treasury.transactions.index'));
  };

  const handleDelete = () => {
    if (deleteTransaction) {
      router.delete(route('treasury.transactions.destroy', deleteTransaction.id), {
        onSuccess: () => setDeleteTransaction(null),
      });
    }
  };

  return (
    <PageShell
      title="Transactions"
      description="Track all your income and expenses"
      actions={
        <div className="flex gap-2">
          <Button
            variant={showFilters ? 'secondary' : 'outline'}
            onClick={() => setShowFilters(!showFilters)}
            className="relative"
          >
            <Filter className="w-4 h-4 mr-2" />
            Filters
            {hasActiveFilters && (
              <span className="absolute -top-1 -right-1 w-3 h-3 bg-primary rounded-full" />
            )}
          </Button>
          {wallets.length > 0 ? (
            <Link href={route('treasury.transactions.create')}>
              <Button>
                <Plus className="w-4 h-4 mr-2" />
                Add Transaction
              </Button>
            </Link>
          ) : (
            <div className="relative group">
              <Button disabled className="opacity-50 cursor-not-allowed">
                <Plus className="w-4 h-4 mr-2" />
                Add Transaction
              </Button>
              <div className="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-3 py-2 bg-popover border rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">
                <p className="text-sm font-medium">Create a wallet first</p>
                <p className="text-xs text-muted-foreground">
                  You need at least one wallet to add transactions
                </p>
                <div className="absolute left-full top-1/2 -translate-y-1/2 -ml-1 border-8 border-transparent border-l-popover" />
              </div>
            </div>
          )}
        </div>
      }
    >
      {/* Summary Stats */}
      {transactions?.data?.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
          <QuickStatCard
            title="Total Income"
            value={formatCurrency(summary.totalIncome)}
            icon={ArrowUpCircle}
            color="emerald"
            compact
          />
          <QuickStatCard
            title="Total Expenses"
            value={formatCurrency(summary.totalExpense)}
            icon={ArrowDownCircle}
            color="rose"
            compact
          />
          <QuickStatCard
            title="Total Savings"
            value={formatCurrency(summary.totalSavings)}
            icon={Target}
            color="blue"
            compact
          />
          <QuickStatCard
            title="Net Flow"
            value={formatCurrency(Math.abs(summary.netFlow))}
            prefix={summary.netFlow >= 0 ? '+' : '-'}
            icon={ArrowRightCircle}
            color={summary.netFlow >= 0 ? 'emerald' : 'rose'}
            compact
          />
          <QuickStatCard
            title="Transactions"
            value={summary.transactionCount}
            icon={Calendar}
            color="primary"
            compact
          />
        </div>
      )}

      {/* Filters Panel */}
      {showFilters && (
        <Card className="mb-6 overflow-hidden">
          <CardHeader className="pb-4 bg-gradient-to-r from-primary/5 to-transparent">
            <div className="flex items-center justify-between">
              <CardTitle className="text-base flex items-center gap-2">
                <Filter className="w-4 h-4" />
                Filter Transactions
              </CardTitle>
              {hasActiveFilters && (
                <Button variant="ghost" size="sm" onClick={clearFilters} className="text-xs">
                  <X className="w-3 h-3 mr-1" />
                  Clear all
                </Button>
              )}
            </div>
          </CardHeader>
          <CardContent className="pt-0">
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div className="space-y-2">
                <Label className="text-xs font-medium text-muted-foreground">Wallet</Label>
                <Select
                  value={filterData.wallet_id}
                  onValueChange={(value) => setFilterData({ ...filterData, wallet_id: value })}
                >
                  <SelectTrigger className="h-9">
                    <SelectValue placeholder="All wallets" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All wallets</SelectItem>
                    {wallets?.map((w) => (
                      <SelectItem key={w.id} value={String(w.id)}>
                        {w.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label className="text-xs font-medium text-muted-foreground">Type</Label>
                <Select
                  value={filterData.type}
                  onValueChange={(value) => setFilterData({ ...filterData, type: value })}
                >
                  <SelectTrigger className="h-9">
                    <SelectValue placeholder="All types" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All types</SelectItem>
                    {types?.map((t) => (
                      <SelectItem key={t} value={t} className="capitalize">
                        <span className="flex items-center gap-2">
                          {t === 'income' && <ArrowUpCircle className="w-3 h-3 text-emerald-500" />}
                          {t === 'expense' && <ArrowDownCircle className="w-3 h-3 text-rose-500" />}
                          {t === 'transfer' && (
                            <ArrowRightCircle className="w-3 h-3 text-blue-500" />
                          )}
                          {t}
                        </span>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label className="text-xs font-medium text-muted-foreground">Category</Label>
                <Select
                  value={filterData.category_id}
                  onValueChange={(value) => setFilterData({ ...filterData, category_id: value })}
                >
                  <SelectTrigger className="h-9">
                    <SelectValue placeholder="All categories" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All categories</SelectItem>
                    {categories?.map((c) => (
                      <SelectItem key={c.id} value={String(c.id)}>
                        <span className="flex items-center gap-2">
                          <span
                            className="w-2 h-2 rounded-full"
                            style={{ backgroundColor: c.color || '#6b7280' }}
                          />
                          {c.name}
                        </span>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label className="text-xs font-medium text-muted-foreground">From</Label>
                <DatePicker
                  value={filterData.start_date}
                  onChange={(date) => setFilterData({ ...filterData, start_date: date })}
                  placeholder="Start date"
                  className="[&_button]:h-9"
                />
              </div>
              <div className="space-y-2">
                <Label className="text-xs font-medium text-muted-foreground">To</Label>
                <DatePicker
                  value={filterData.end_date}
                  onChange={(date) => setFilterData({ ...filterData, end_date: date })}
                  placeholder="End date"
                  className="[&_button]:h-9"
                />
              </div>
            </div>
            <div className="flex justify-end gap-2 mt-4 pt-4 border-t">
              <Button variant="ghost" onClick={clearFilters} size="sm">
                Reset
              </Button>
              <Button onClick={applyFilters} size="sm">
                Apply Filters
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Transactions List - Grouped by Date */}
      {groupedTransactions.length > 0 ? (
        <div className="space-y-4">
          {groupedTransactions.map((group) => (
            <Card
              key={group.date}
              className="overflow-hidden border-border/50 shadow-sm hover:shadow-md transition-shadow"
            >
              {/* Date Header - Fixed overlapping for mobile */}
              <div className="px-3 md:px-4 py-3 bg-muted/30 border-b border-border/50 flex flex-col sm:flex-row sm:items-center justify-between gap-y-3 gap-x-4">
                <div className="flex items-center gap-3">
                  <div className="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-background border border-border/50 flex items-center justify-center shrink-0 shadow-sm">
                    <Calendar className="w-4 h-4 md:w-5 md:h-5 text-muted-foreground" />
                  </div>
                  <div className="min-w-0">
                    <p className="font-bold text-sm md:text-base text-foreground leading-tight">
                      {group.label}
                    </p>
                    <p className="text-[11px] md:text-xs text-muted-foreground">
                      {group.transactions.length} transactions
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-x-3 gap-y-1.5 sm:justify-end flex-wrap text-[10px] md:text-xs">
                  {group.totalIncome > 0 && (
                    <div className="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-500/5 px-2 py-1 rounded-md border border-emerald-500/10 whitespace-nowrap">
                      <ArrowUpCircle className="w-3 h-3 md:w-3.5 md:h-3.5" />
                      <span>+{formatCurrency(group.totalIncome)}</span>
                    </div>
                  )}
                  {group.totalExpense > 0 && (
                    <div className="flex items-center gap-1 text-rose-600 dark:text-rose-400 font-bold bg-rose-500/5 px-2 py-1 rounded-md border border-rose-500/10 whitespace-nowrap">
                      <ArrowDownCircle className="w-3 h-3 md:w-3.5 md:h-3.5" />
                      <span>-{formatCurrency(group.totalExpense)}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Transactions for this date */}
              <div className="divide-y divide-border/40">
                {group.transactions.map((tx) => (
                  <TransactionRow key={tx.id} tx={tx} onDelete={setDeleteTransaction} />
                ))}
              </div>
            </Card>
          ))}

          {/* Pagination */}
          {transactions.links && transactions.links.length > 3 && (
            <Card className="border-border/50">
              <CardContent className="p-4">
                <div className="flex items-center justify-between">
                  <p className="text-sm text-muted-foreground">
                    Showing {transactions.from} to {transactions.to} of {transactions.total}{' '}
                    transactions
                  </p>
                  <div className="flex gap-1">
                    {transactions.links.map((link, index) => {
                      if (index === 0 || index === transactions.links.length - 1) {
                        const isPrev = index === 0;
                        return (
                          <Link
                            key={index}
                            href={link.url || '#'}
                            className={cn(
                              'flex items-center justify-center w-8 h-8 rounded-lg text-sm transition-colors',
                              link.url
                                ? 'bg-muted hover:bg-muted/80'
                                : 'text-muted-foreground/50 cursor-not-allowed'
                            )}
                          >
                            {isPrev ? (
                              <ChevronLeft className="w-4 h-4" />
                            ) : (
                              <ChevronRight className="w-4 h-4" />
                            )}
                          </Link>
                        );
                      }
                      return (
                        <Link
                          key={index}
                          href={link.url || '#'}
                          className={cn(
                            'flex items-center justify-center w-8 h-8 rounded-lg text-sm transition-colors',
                            link.active && 'bg-primary text-primary-foreground font-medium',
                            !link.active && link.url && 'bg-muted hover:bg-muted/80',
                            !link.url && 'text-muted-foreground/50 cursor-not-allowed'
                          )}
                          dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                      );
                    })}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      ) : (
        <Card className="border-dashed border-2">
          <CardContent className="py-16">
            <EmptyState
              icon={ArrowDownCircle}
              message="No transactions yet. Start tracking your money!"
            />
          </CardContent>
        </Card>
      )}

      <ConfirmDialog
        isOpen={!!deleteTransaction}
        onCancel={() => setDeleteTransaction(null)}
        title="Delete Transaction"
        message="Are you sure you want to delete this transaction? This action cannot be undone."
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
