/**
 * Treasury Index page - Main landing page for Treasury module
 * Shows overview of wallets, goals, budgets, and recent transactions
 *
 * Note: DashboardLayout is applied automatically via app.jsx persistent layouts
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { useDisclosure } from '@/Hooks/useDisclosure';
import BudgetPieChart from '@Treasury/Components/BudgetPieChart';
import { BudgetStatusGroup } from '@Treasury/Components/BudgetStatusIndicator';
import EmptyState from '@Treasury/Components/EmptyState';
import GoalProgressItem from '@Treasury/Components/GoalProgressItem';
import IncomeExpenseChart from '@Treasury/Components/IncomeExpenseChart';
import FinancialHealthModal from '@Treasury/Components/Modals/FinancialHealthModal';
import NetWorthBanner from '@Treasury/Components/NetWorthBanner';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import SectionCard from '@Treasury/Components/SectionCard';
import TransactionItem from '@Treasury/Components/TransactionItem';
import WalletListItem from '@Treasury/Components/WalletListItem';
import { Activity, ArrowDownCircle, ArrowUpCircle, PieChart, Target, Wallet } from 'lucide-react';

import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';

export default function Index({
  netWorth,
  wallets = [],
  goals = [],
  budgetSummary,
  budgets = [],
  recentTransactions = [],
  monthlySummary,
  monthlyTrend = [],
  financialHealth = {},
}) {
  const { formatCurrency } = useAppearance();
  const healthDialog = useDisclosure();

  return (
    <PageShell
      title="Treasury"
      description="Manage your finances, track expenses, and achieve your goals"
    >
      {/* Net Worth Banner */}
      <div className="mb-6">
        <NetWorthBanner total={netWorth?.total || 0} walletCount={netWorth?.wallet_count || 0} />
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <QuickStatCard title="Wallets" value={wallets?.length || 0} icon={Wallet} color="blue" />
        <QuickStatCard
          title="Active Goals"
          value={goals?.length || 0}
          icon={Target}
          color="amber"
        />
        <QuickStatCard
          title="Income"
          value={formatCurrency(monthlySummary?.income || 0)}
          icon={ArrowUpCircle}
          color="green"
          valueClassName="text-xl text-green-600"
        />
        <QuickStatCard
          title="Expenses"
          value={formatCurrency(monthlySummary?.expense || 0)}
          icon={ArrowDownCircle}
          color="red"
          valueClassName="text-xl text-red-600"
        />
      </div>

      {/* Income vs Expenses Trend Chart */}
      {monthlyTrend && monthlyTrend.length > 0 && (
        <SectionCard
          title="Income vs Expenses Trend (Last 6 Months)"
          className="mb-6"
          action={
            <Button variant="ghost" size="sm" onClick={healthDialog.onOpen} className="gap-2">
              <Activity className="h-4 w-4" />
              Financial Health
            </Button>
          }
        >
          <IncomeExpenseChart data={monthlyTrend} />
        </SectionCard>
      )}

      {/* Financial Health Dialog */}
      <FinancialHealthModal
        open={healthDialog.isOpen}
        onOpenChange={(open) => !open && healthDialog.onClose()}
        data={financialHealth}
      />

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Wallets Section */}
        <SectionCard title="Wallets" viewAllRoute={route('treasury.wallets.index')}>
          {wallets && wallets.length > 0 ? (
            <div className="space-y-3">
              {wallets.slice(0, 4).map((wallet) => (
                <WalletListItem key={wallet.id} wallet={wallet} />
              ))}
            </div>
          ) : (
            <EmptyState
              icon={Wallet}
              message="No wallets yet"
              actionLabel="Add Wallet"
              actionRoute={route('treasury.wallets.create')}
            />
          )}
        </SectionCard>

        {/* Goals Section */}
        <SectionCard title="Savings Goals" viewAllRoute={route('treasury.goals.index')}>
          {goals && goals.length > 0 ? (
            <div className="space-y-4">
              {goals.slice(0, 3).map((goal) => (
                <GoalProgressItem key={goal.id} goal={goal} />
              ))}
            </div>
          ) : wallets.some((w) => w.type === 'savings') ? (
            <EmptyState
              icon={Target}
              message="No goals yet"
              actionLabel="Create Goal"
              actionRoute={route('treasury.goals.create')}
            />
          ) : (
            <EmptyState
              icon={Target}
              message="Create a savings wallet first to set goals"
              actionLabel="Create Wallet"
              actionRoute={route('treasury.wallets.create')}
            />
          )}
        </SectionCard>

        {/* Budget Summary Section with Pie Chart */}
        <SectionCard title="Budget Overview" viewAllRoute={route('treasury.budgets.index')}>
          {budgetSummary && (budgets.length > 0 || budgetSummary.total_budgeted > 0) ? (
            <div className="space-y-4">
              {/* Pie Chart */}
              {budgets.length > 0 && <BudgetPieChart budgets={budgets} />}

              {/* Summary Stats */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center border-t pt-4">
                <div className="flex flex-col sm:block">
                  <p className="text-xs sm:text-sm text-muted-foreground order-2 sm:order-1">
                    Budgeted
                  </p>
                  <p className="text-base sm:text-lg font-bold order-1 sm:order-2">
                    {formatCurrency(budgetSummary.total_budgeted || 0)}
                  </p>
                </div>
                <div className="flex flex-col sm:block border-t sm:border-t-0 pt-2 sm:pt-0">
                  <p className="text-xs sm:text-sm text-muted-foreground order-2 sm:order-1">
                    Spent
                  </p>
                  <p className="text-base sm:text-lg font-bold text-red-600 order-1 sm:order-2">
                    {formatCurrency(budgetSummary.total_spent || 0)}
                  </p>
                </div>
                <div className="flex flex-col sm:block border-t sm:border-t-0 pt-2 sm:pt-0">
                  <p className="text-xs sm:text-sm text-muted-foreground order-2 sm:order-1">
                    Remaining
                  </p>
                  <p className="text-base sm:text-lg font-bold text-green-600 order-1 sm:order-2">
                    {formatCurrency(budgetSummary.total_remaining || 0)}
                  </p>
                </div>
              </div>

              {/* Budget Status Indicators */}
              <div className="pt-2 sm:pt-0">
                <BudgetStatusGroup
                  safeCount={budgetSummary.safe_count || 0}
                  warningCount={budgetSummary.warning_count || 0}
                  overCount={budgetSummary.overbudget_count || 0}
                />
              </div>
            </div>
          ) : (
            <EmptyState
              icon={PieChart}
              message="No budgets set"
              actionLabel="Set Budget"
              actionRoute={route('treasury.budgets.create')}
            />
          )}
        </SectionCard>

        {/* Recent Transactions Section */}
        <SectionCard
          title="Recent Transactions"
          viewAllRoute={route('treasury.transactions.index')}
        >
          {recentTransactions && recentTransactions.length > 0 ? (
            <div className="space-y-1">
              {recentTransactions.slice(0, 5).map((tx) => (
                <TransactionItem key={tx.id} transaction={tx} />
              ))}
            </div>
          ) : wallets.length > 0 ? (
            <EmptyState
              icon={ArrowDownCircle}
              message="No transactions yet"
              actionLabel="Add Transaction"
              actionRoute={route('treasury.transactions.create')}
            />
          ) : (
            <EmptyState
              icon={ArrowDownCircle}
              message="Create a wallet first to start tracking transactions"
              actionLabel="Create Wallet"
              actionRoute={route('treasury.wallets.create')}
            />
          )}
        </SectionCard>
      </div>
    </PageShell>
  );
}
