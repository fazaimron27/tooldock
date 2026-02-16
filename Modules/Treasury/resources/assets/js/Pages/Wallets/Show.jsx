/**
 * Wallet Show page - View wallet details and transactions
 * Wallet type info is fetched from Categories system
 */
import { useAppearance } from '@/Hooks/useAppearance';
import EmptyState from '@Treasury/Components/EmptyState';
import SectionCard from '@Treasury/Components/SectionCard';
import TransactionItem from '@Treasury/Components/TransactionItem';
import { getWalletColor, getWalletIcon } from '@Treasury/Utils/walletIcons';
import { Link, router } from '@inertiajs/react';
import { ArrowDownCircle, ArrowLeft, CheckCircle, Pencil, Target, Trash2 } from 'lucide-react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import ProgressBar from '@/Components/Common/ProgressBar';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

export default function Show({
  wallet,
  walletType,
  walletStats = {},
  recentTransactions = [],
  goals = [],
}) {
  const { formatCurrency, formatDate } = useAppearance();
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const WalletIcon = getWalletIcon(wallet.type);
  // Use wallet type color from Categories, fallback to utility function
  const walletColor = walletType?.color || getWalletColor(wallet.type);
  // Get display name from Categories, fallback to raw type
  const walletTypeName = walletType?.name || wallet.type;

  const handleDelete = () => {
    router.delete(route('treasury.wallets.destroy', wallet.id));
  };

  const activeGoals = goals.filter((g) => !g.is_completed);
  const completedGoals = goals.filter((g) => g.is_completed);

  return (
    <PageShell
      title={wallet.name}
      description={`${walletTypeName} wallet`}
      backLink={route('treasury.wallets.index')}
      backLabel="Back to Wallets"
      actions={
        <div className="flex gap-2">
          <Link href={route('treasury.wallets.index')}>
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back
            </Button>
          </Link>
          <Button
            variant="outline"
            onClick={() => setShowDeleteConfirm(true)}
            className="text-destructive hover:text-destructive hover:bg-destructive/10"
          >
            <Trash2 className="w-4 h-4 mr-2" />
            Delete
          </Button>
          <Link href={route('treasury.wallets.edit', wallet.id)}>
            <Button>
              <Pencil className="w-4 h-4 mr-2" />
              Edit
            </Button>
          </Link>
        </div>
      }
    >
      {/* Balance Card */}
      <Card className="mb-6">
        <CardContent className="pt-6">
          <div className="flex items-center gap-4">
            <div
              className="w-14 h-14 rounded-xl flex items-center justify-center"
              style={{ backgroundColor: `${walletColor}15` }}
            >
              <WalletIcon className="w-7 h-7" style={{ color: walletColor }} />
            </div>
            <div>
              <p className="text-sm text-muted-foreground">Current Balance</p>
              <p className="text-3xl font-bold">
                {formatCurrency(wallet.balance, wallet.currency)}
              </p>
              {wallet.description && (
                <p className="text-sm text-muted-foreground mt-1">{wallet.description}</p>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Wallet Info */}
        <Card>
          <CardHeader>
            <CardTitle>Wallet Details</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-muted-foreground">Type</span>
              <span className="font-medium flex items-center gap-2">
                {walletType?.color && (
                  <span
                    className="w-3 h-3 rounded-full"
                    style={{ backgroundColor: walletType.color }}
                  />
                )}
                {walletTypeName}
              </span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-muted-foreground">Currency</span>
              <span className="font-medium">{wallet.currency}</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-muted-foreground">Status</span>
              <Badge variant={wallet.is_active ? 'default' : 'secondary'}>
                {wallet.is_active ? 'Active' : 'Inactive'}
              </Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-muted-foreground">Created</span>
              <span className="font-medium">{formatDate(wallet.created_at)}</span>
            </div>
            {wallet.description && (
              <div className="pt-2 border-t">
                <span className="text-sm text-muted-foreground">Description</span>
                <p className="mt-1 text-sm">{wallet.description}</p>
              </div>
            )}

            {/* Transaction Stats */}
            <div className="pt-3 border-t space-y-2">
              <h4 className="text-sm font-medium text-muted-foreground">Transaction Summary</h4>
              {wallet.type === 'savings' ? (
                /* Savings wallet: Income, Transfers In/Out (no expense) */
                <div className="space-y-2">
                  <div className="p-2 rounded-lg bg-green-50 dark:bg-green-950/30">
                    <p className="text-xs text-muted-foreground">Total Income</p>
                    <p className="font-semibold text-green-600 dark:text-green-400">
                      +{formatCurrency(walletStats.total_income || 0, wallet.currency)}
                    </p>
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <div className="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/30">
                      <p className="text-xs text-muted-foreground">Transfers In</p>
                      <p className="font-semibold text-blue-600 dark:text-blue-400">
                        +{formatCurrency(walletStats.total_transfers_in || 0, wallet.currency)}
                      </p>
                    </div>
                    <div className="p-2 rounded-lg bg-orange-50 dark:bg-orange-950/30">
                      <p className="text-xs text-muted-foreground">Transfers Out</p>
                      <p className="font-semibold text-orange-600 dark:text-orange-400">
                        -{formatCurrency(walletStats.total_transfers_out || 0, wallet.currency)}
                      </p>
                    </div>
                  </div>
                  <p className="text-xs text-muted-foreground text-center mt-2 p-2 bg-muted/50 rounded">
                    💡 Savings wallets cannot be used for expenses
                  </p>
                </div>
              ) : (
                /* Regular wallet: All 4 stats */
                <div className="grid grid-cols-2 gap-3">
                  <div className="p-2 rounded-lg bg-green-50 dark:bg-green-950/30">
                    <p className="text-xs text-muted-foreground">Total Income</p>
                    <p className="font-semibold text-green-600 dark:text-green-400">
                      +{formatCurrency(walletStats.total_income || 0, wallet.currency)}
                    </p>
                  </div>
                  <div className="p-2 rounded-lg bg-red-50 dark:bg-red-950/30">
                    <p className="text-xs text-muted-foreground">Total Expense</p>
                    <p className="font-semibold text-red-600 dark:text-red-400">
                      -{formatCurrency(walletStats.total_expense || 0, wallet.currency)}
                    </p>
                  </div>
                  <div className="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/30">
                    <p className="text-xs text-muted-foreground">Transfers In</p>
                    <p className="font-semibold text-blue-600 dark:text-blue-400">
                      +{formatCurrency(walletStats.total_transfers_in || 0, wallet.currency)}
                    </p>
                  </div>
                  <div className="p-2 rounded-lg bg-orange-50 dark:bg-orange-950/30">
                    <p className="text-xs text-muted-foreground">Transfers Out</p>
                    <p className="font-semibold text-orange-600 dark:text-orange-400">
                      -{formatCurrency(walletStats.total_transfers_out || 0, wallet.currency)}
                    </p>
                  </div>
                </div>
              )}
              <p className="text-xs text-muted-foreground text-center pt-1">
                {walletStats.transaction_count || 0} total transactions
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Recent Transactions */}
        <SectionCard
          title="Recent Transactions"
          viewAllRoute={route('treasury.transactions.index', { wallet_id: wallet.id })}
        >
          {recentTransactions && recentTransactions.length > 0 ? (
            <div className="space-y-1">
              {recentTransactions.slice(0, 5).map((tx) => (
                <TransactionItem key={tx.id} transaction={tx} />
              ))}
            </div>
          ) : (
            <EmptyState icon={ArrowDownCircle} message="No transactions yet" />
          )}
        </SectionCard>
      </div>

      {/* Linked Goals Section */}
      {goals.length > 0 && (
        <Card className="mt-6">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Target className="w-5 h-5" />
              Linked Goals
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {/* Active Goals */}
              {activeGoals.length > 0 && (
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground mb-3">
                    Active ({activeGoals.length})
                  </h4>
                  <div className="space-y-3">
                    {activeGoals.map((goal) => (
                      <Link
                        key={goal.id}
                        href={route('treasury.goals.show', goal.id)}
                        className="block p-3 rounded-lg border hover:bg-muted/50 transition-colors"
                      >
                        <div className="flex justify-between items-start mb-2">
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{goal.name}</span>
                            {goal.category && (
                              <Badge
                                variant="outline"
                                style={{
                                  borderColor: goal.category.color,
                                  color: goal.category.color,
                                }}
                              >
                                {goal.category.name}
                              </Badge>
                            )}
                          </div>
                          <span className="text-sm font-bold">{goal.progress ?? 0}%</span>
                        </div>
                        <ProgressBar
                          percentage={goal.progress ?? 0}
                          value=""
                          label=""
                          color={goal.category?.color || '#10b981'}
                        />
                        <div className="flex justify-between text-xs text-muted-foreground mt-2">
                          <span>
                            {formatCurrency(goal.saved_amount, goal.currency)} /{' '}
                            {formatCurrency(goal.target_amount, goal.currency)}
                          </span>
                          {goal.deadline && <span>Due: {formatDate(goal.deadline)}</span>}
                        </div>
                      </Link>
                    ))}
                  </div>
                </div>
              )}

              {/* Completed Goals */}
              {completedGoals.length > 0 && (
                <div>
                  <h4 className="text-sm font-medium text-muted-foreground mb-3 flex items-center gap-2">
                    <CheckCircle className="w-4 h-4 text-green-500" />
                    Completed ({completedGoals.length})
                  </h4>
                  <div className="space-y-2">
                    {completedGoals.map((goal) => (
                      <Link
                        key={goal.id}
                        href={route('treasury.goals.show', goal.id)}
                        className="flex justify-between items-center p-3 rounded-lg border bg-green-50/50 dark:bg-green-950/20 hover:bg-green-50 dark:hover:bg-green-950/30 transition-colors"
                      >
                        <div className="flex items-center gap-2">
                          <CheckCircle className="w-4 h-4 text-green-500" />
                          <span className="font-medium">{goal.name}</span>
                        </div>
                        <span className="text-sm text-muted-foreground">
                          {formatCurrency(goal.target_amount, goal.currency)}
                        </span>
                      </Link>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      <ConfirmDialog
        isOpen={showDeleteConfirm}
        onCancel={() => setShowDeleteConfirm(false)}
        title="Delete Wallet"
        message={`Are you sure you want to delete "${wallet.name}"? This will also delete all transactions associated with this wallet. This action cannot be undone.`}
        onConfirm={handleDelete}
        confirmLabel="Delete Wallet"
        variant="destructive"
      />
    </PageShell>
  );
}
