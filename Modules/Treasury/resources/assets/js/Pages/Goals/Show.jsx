/**
 * Goal Show page - View goal details and add funds
 * Uses useInertiaForm for the inline allocation form
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import CurrencyInputRHF from '@Treasury/Components/FormFields/CurrencyInputRHF';
import SearchableSelectRHF from '@Treasury/Components/FormFields/SearchableSelectRHF';
import { allocateResolver } from '@Treasury/Schemas/allocateSchemas';
import { getGoalIcon } from '@Treasury/Utils/goalIcons';
import { Link, router } from '@inertiajs/react';
import {
  AlertTriangle,
  ArrowDownCircle,
  ArrowLeft,
  Calendar,
  CheckCircle,
  Pencil,
  Plus,
  Trash2,
  Wallet,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import ProgressBar from '@/Components/Common/ProgressBar';
import PageShell from '@/Components/Layouts/PageShell';
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Show({ goal, transactions, wallets, exchangeRates }) {
  const { formatCurrency, formatDate } = useAppearance();
  const [showAllocate, setShowAllocate] = useState(false);
  const [showDelete, setShowDelete] = useState(false);

  const IconComponent = getGoalIcon(goal.category?.slug);
  const categoryColor = goal.category?.color || '#6B7280';

  const allocateForm = useInertiaForm(
    {
      wallet_id: '',
      amount: '',
      description: '',
    },
    {
      resolver: allocateResolver,
      toast: {
        success: 'Funds allocated successfully!',
        error: 'Failed to allocate funds. Please check the form for errors.',
      },
    }
  );

  const sourceWallets = useMemo(
    () => wallets?.filter((w) => w.id !== goal.wallet_id) || [],
    [wallets, goal.wallet_id]
  );

  useEffect(() => {
    if (sourceWallets.length > 0 && !allocateForm.watch('wallet_id')) {
      const walletWithHighestBalance = sourceWallets.reduce((max, w) =>
        parseFloat(w.balance || 0) > parseFloat(max.balance || 0) ? w : max
      );
      allocateForm.setValue('wallet_id', String(walletWithHighestBalance.id));
    }
  }, [sourceWallets, allocateForm]);

  const walletId = allocateForm.watch('wallet_id');
  const amount = allocateForm.watch('amount');

  const selectedWallet = useMemo(
    () => wallets?.find((w) => String(w.id) === String(walletId)),
    [wallets, walletId]
  );

  const isCrossCurrencyAllocation = useMemo(() => {
    if (!selectedWallet || !goal.currency) return false;
    return selectedWallet.currency !== goal.currency;
  }, [selectedWallet, goal.currency]);
  const conversionInfo = useMemo(() => {
    if (!isCrossCurrencyAllocation || !exchangeRates) return null;

    const walletCurrency = selectedWallet?.currency;
    const goalCurrency = goal.currency;

    const walletRate = exchangeRates[walletCurrency];
    const goalRate = exchangeRates[goalCurrency];

    if (!walletRate || !goalRate) {
      return { available: false };
    }

    const rate = goalRate / walletRate;
    const amountNum = parseFloat(amount) || 0;
    const convertedAmount = amountNum * rate;

    return {
      available: true,
      rate,
      convertedAmount,
      walletCurrency,
      goalCurrency,
    };
  }, [isCrossCurrencyAllocation, exchangeRates, selectedWallet, goal.currency, amount]);

  const isOverBalance = useMemo(() => {
    if (!selectedWallet) return false;
    const amountNum = parseFloat(amount) || 0;
    const walletBalance = parseFloat(selectedWallet.balance) || 0;
    return amountNum > walletBalance;
  }, [selectedWallet, amount]);

  const handleAllocate = (e) => {
    e.preventDefault();
    allocateForm.post(route('treasury.goals.allocate', goal.id), {
      onSuccess: () => {
        allocateForm.reset();
        setShowAllocate(false);
      },
    });
  };

  const getDeleteMessage = () => {
    const savedAmount = parseFloat(goal.saved_amount) || 0;
    const formattedAmount = formatCurrency(savedAmount, goal.currency);

    if (goal.is_completed) {
      return `This completed goal has ${formattedAmount} allocated. The funds will remain in your savings wallet. Delete "${goal.name}"?`;
    } else if (savedAmount > 0) {
      return `This goal has ${formattedAmount} allocated (${goal.progress}% progress). The funds will remain in your savings wallet. Delete "${goal.name}"?`;
    }
    return `Are you sure you want to delete "${goal.name}"? This action cannot be undone.`;
  };

  const handleDelete = () => {
    router.delete(route('treasury.goals.destroy', goal.id));
  };

  return (
    <PageShell
      title={goal.name}
      backLink={route('treasury.goals.index')}
      backLabel="Back to Goals"
      actions={
        <div className="flex flex-wrap md:flex-nowrap items-center gap-2 w-full md:w-auto">
          <Link href={route('treasury.goals.index')} className="hidden md:block">
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back
            </Button>
          </Link>
          <Button onClick={() => setShowAllocate(!showAllocate)} className="flex-1 md:flex-initial">
            <Plus className="w-4 h-4 mr-2" />
            Add Funds
          </Button>
          <Link href={route('treasury.goals.edit', goal.id)} className="flex-1 md:flex-initial">
            <Button variant="outline" className="w-full">
              <Pencil className="w-4 h-4 mr-2" />
              Edit
            </Button>
          </Link>
          <Button
            variant="destructive"
            onClick={() => setShowDelete(true)}
            size="icon"
            className="md:w-auto md:px-4 md:py-2"
          >
            <Trash2 className="w-4 h-4 md:mr-2" />
            <span className="hidden md:inline">Delete</span>
          </Button>
        </div>
      }
    >
      {/* Hero Card */}
      <Card className="mb-6 overflow-hidden">
        {/* Color accent bar */}
        <div className="h-2" style={{ backgroundColor: categoryColor }} />

        <CardContent className="pt-6">
          <div className="flex flex-col lg:flex-row lg:items-start gap-6">
            {/* Icon and Info */}
            <div className="flex items-start gap-4 flex-1">
              <div
                className="p-3 md:p-4 rounded-2xl shrink-0"
                style={{ backgroundColor: categoryColor }}
              >
                {goal.is_completed ? (
                  <CheckCircle className="w-6 h-6 md:w-8 md:h-8 text-white" />
                ) : (
                  <IconComponent className="w-6 h-6 md:w-8 md:h-8 text-white" />
                )}
              </div>
              <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2 flex-wrap mb-1">
                  <h2 className="text-xl md:text-2xl font-bold truncate">{goal.name}</h2>
                  {goal.is_completed && (
                    <span className="inline-flex items-center gap-1 text-[10px] md:text-xs font-medium text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-0.5 md:py-1 rounded-full">
                      <CheckCircle className="w-3 h-3" />
                      Completed
                    </span>
                  )}
                </div>

                {/* Category & Metadata */}
                <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs md:text-sm text-muted-foreground mt-2">
                  {goal.category && (
                    <div className="flex items-center gap-1.5">
                      <div
                        className="w-2.5 h-2.5 rounded-full"
                        style={{ backgroundColor: categoryColor }}
                      />
                      <span>{goal.category.name}</span>
                    </div>
                  )}
                  {goal.wallet && (
                    <div className="flex items-center gap-1.5">
                      <Wallet className="w-4 h-4" />
                      <span>{goal.wallet.name}</span>
                    </div>
                  )}
                  {goal.deadline && (
                    <div
                      className={`flex items-center gap-1.5 ${goal.is_overdue ? 'text-red-500' : ''}`}
                    >
                      <Calendar className="w-4 h-4" />
                      <span>Due: {formatDate(goal.deadline)}</span>
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-2 gap-4 border-t pt-6 lg:border-t-0 lg:pt-0 lg:flex lg:flex-col lg:items-end lg:gap-2">
              <div className="text-left lg:text-right">
                <p className="text-xs md:text-sm text-muted-foreground">
                  Saved {goal.currency && <span className="text-xs">({goal.currency})</span>}
                </p>
                <p className="text-lg md:text-2xl font-bold text-green-600">
                  {formatCurrency(goal.saved_amount ?? 0, goal.currency)}
                </p>
              </div>
              <div className="text-left lg:text-right">
                <p className="text-xs md:text-sm text-muted-foreground">Remaining</p>
                <p className="text-lg md:text-2xl font-bold">
                  {formatCurrency(goal.remaining, goal.currency)}
                </p>
              </div>
            </div>
          </div>

          {/* Progress */}
          <div className="mt-8 pt-6 pb-2 border-t space-y-3">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-y-1 text-sm">
              <div className="flex items-center justify-between sm:justify-start gap-2">
                <span className="font-semibold">{goal.progress}% complete</span>
                <span className="text-xs text-muted-foreground sm:hidden tracking-tight">
                  Target: {formatCurrency(goal.target_amount, goal.currency)}
                </span>
              </div>
              <span className="hidden sm:inline text-muted-foreground">
                Target: {formatCurrency(goal.target_amount, goal.currency)}
              </span>
            </div>
            <ProgressBar value={goal.progress} className="h-2.5 rounded-full" />
          </div>
        </CardContent>
      </Card>

      {/* Allocate Form */}
      {showAllocate && (
        <Card className="mb-6 border-primary/20 bg-primary/5">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Plus className="w-5 h-5" />
              Transfer Funds to Savings
            </CardTitle>
            <p className="text-sm text-muted-foreground">
              Transfer from any wallet to {goal.wallet?.name || 'linked savings wallet'}
            </p>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleAllocate} className="space-y-4" noValidate>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchableSelectRHF
                  name="wallet_id"
                  control={allocateForm.control}
                  label="From Wallet"
                  required
                  options={sourceWallets.map((wallet) => ({
                    value: String(wallet.id),
                    label: `${wallet.name} (${formatCurrency(wallet.balance, wallet.currency)})`,
                    description: wallet.currency,
                  }))}
                  placeholder="Select source wallet"
                  searchPlaceholder="Search wallets..."
                  emptyMessage="No wallets available"
                />

                <CurrencyInputRHF
                  name="amount"
                  control={allocateForm.control}
                  label="Amount"
                  required
                  placeholder="0"
                  currencyCode={selectedWallet?.currency || goal.currency}
                />

                <div className="flex items-end gap-2">
                  <Button
                    type="submit"
                    disabled={
                      allocateForm.formState.isSubmitting ||
                      sourceWallets.length === 0 ||
                      isOverBalance
                    }
                    className="flex-1"
                  >
                    {allocateForm.formState.isSubmitting ? 'Transferring...' : 'Transfer'}
                  </Button>
                  <Button type="button" variant="outline" onClick={() => setShowAllocate(false)}>
                    Cancel
                  </Button>
                </div>
              </div>

              {/* Insufficient balance warning */}
              {isOverBalance && selectedWallet && (
                <Alert className="mt-4 border-amber-500/30 bg-amber-500/10">
                  <AlertTriangle className="h-4 w-4 text-amber-500" />
                  <AlertDescription className="text-sm">
                    <strong className="text-amber-600 dark:text-amber-400">
                      Insufficient balance:
                    </strong>{' '}
                    Amount{' '}
                    <strong>
                      {formatCurrency(parseFloat(amount) || 0, selectedWallet.currency)}
                    </strong>{' '}
                    exceeds available balance of{' '}
                    <strong>
                      {formatCurrency(
                        parseFloat(selectedWallet.balance) || 0,
                        selectedWallet.currency
                      )}
                    </strong>
                  </AlertDescription>
                </Alert>
              )}

              {/* Cross-currency allocation info */}
              {isCrossCurrencyAllocation && (
                <Alert className="mt-4 border-blue-500/30 bg-blue-500/10">
                  <AlertTriangle className="h-4 w-4 text-blue-500" />
                  <AlertDescription className="text-sm">
                    {conversionInfo?.available ? (
                      <>
                        <strong>Cross-currency transfer:</strong>{' '}
                        {parseFloat(amount || 0).toLocaleString()} {conversionInfo.walletCurrency} →{' '}
                        <span className="font-semibold text-green-600">
                          {conversionInfo.convertedAmount.toLocaleString(undefined, {
                            maximumFractionDigits: 2,
                          })}{' '}
                          {conversionInfo.goalCurrency}
                        </span>
                        <span className="text-xs text-muted-foreground ml-2">
                          (Rate: 1 {conversionInfo.walletCurrency} ={' '}
                          {conversionInfo.rate.toFixed(4)} {conversionInfo.goalCurrency})
                        </span>
                      </>
                    ) : (
                      <>
                        <strong>Cross-currency transfer:</strong> Exchange rate not available for{' '}
                        {selectedWallet?.currency} → {goal.currency}.
                      </>
                    )}
                  </AlertDescription>
                </Alert>
              )}
            </form>
          </CardContent>
        </Card>
      )}

      {/* Transactions */}
      {transactions?.data && transactions.data.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ArrowDownCircle className="w-5 h-5 text-green-600" />
                Transfer History
              </div>
              {transactions.total > 0 && (
                <span className="text-sm font-normal text-muted-foreground">
                  {transactions.total} transfer{transactions.total !== 1 ? 's' : ''}
                </span>
              )}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-1">
              {transactions.data.map((tx) => (
                <div
                  key={tx.id}
                  className="flex items-start justify-between py-3 px-3 -mx-3 rounded-lg hover:bg-muted/50 transition-colors gap-3"
                >
                  <div className="flex items-start gap-3 min-w-0">
                    <div className="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 shrink-0">
                      <ArrowDownCircle className="w-4 h-4 text-green-600" />
                    </div>
                    <div className="min-w-0">
                      <p className="font-semibold text-sm truncate">
                        {tx.description || 'Goal allocation'}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        From {tx.wallet?.name} • {formatDate(tx.date)}
                      </p>
                    </div>
                  </div>
                  <p className="font-bold text-green-600 whitespace-nowrap text-sm md:text-base">
                    +{formatCurrency(tx.amount, tx.wallet?.currency)}
                  </p>
                </div>
              ))}
            </div>

            {/* Pagination */}
            {transactions.last_page > 1 && (
              <div className="flex items-center justify-between mt-6 pt-4 border-t">
                <p className="text-sm text-muted-foreground">
                  Page {transactions.current_page} of {transactions.last_page}
                </p>
                <div className="flex items-center gap-2">
                  {transactions.links?.map((link, index) => (
                    <Button
                      key={index}
                      variant={link.active ? 'default' : 'outline'}
                      size="sm"
                      disabled={!link.url}
                      asChild={!!link.url}
                    >
                      {link.url ? (
                        <Link href={link.url} preserveScroll>
                          <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Link>
                      ) : (
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                      )}
                    </Button>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Empty state for transactions */}
      {(!transactions?.data || transactions.data.length === 0) && (
        <Card>
          <CardContent className="py-12 text-center">
            <div className="flex flex-col items-center gap-3">
              <div className="p-3 rounded-full bg-muted">
                <ArrowDownCircle className="w-6 h-6 text-muted-foreground" />
              </div>
              <div>
                <p className="font-medium">No allocations yet</p>
                <p className="text-sm text-muted-foreground">
                  Click "Add Funds" to start saving towards this goal
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <ConfirmDialog
        isOpen={showDelete}
        onCancel={() => setShowDelete(false)}
        onConfirm={handleDelete}
        title="Delete Goal"
        message={getDeleteMessage()}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
