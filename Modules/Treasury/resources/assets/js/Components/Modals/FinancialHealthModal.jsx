/**
 * Financial Health Summary Dialog
 * Shows a comprehensive overview of financial health metrics
 * Uses the same dialog pattern as FinancialTipModal
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { ArrowDownCircle, ArrowUpCircle, TrendingDown, TrendingUp, Wallet } from 'lucide-react';

import ProgressBar from '@/Components/Common/ProgressBar';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

function MetricCard({ label, value, icon: Icon, trend, trendLabel, className = '' }) {
  return (
    <div className={`p-4 border rounded-lg bg-background ${className}`}>
      <div className="flex items-center gap-2 mb-1">
        {Icon && <Icon className="h-4 w-4 text-muted-foreground" />}
        <span className="text-sm text-muted-foreground">{label}</span>
      </div>
      <p className="text-xl font-bold font-mono">{value}</p>
      {trend !== undefined && (
        <div className="flex items-center gap-1 mt-1 text-xs">
          {trend >= 0 ? (
            <TrendingUp className="h-3 w-3 text-green-600" />
          ) : (
            <TrendingDown className="h-3 w-3 text-red-600" />
          )}
          <span className={trend >= 0 ? 'text-green-600' : 'text-red-600'}>
            {trend >= 0 ? '+' : ''}
            {trend.toFixed(1)}%
          </span>
          {trendLabel && <span className="text-muted-foreground">{trendLabel}</span>}
        </div>
      )}
    </div>
  );
}

function HealthIndicator({ label, percentage, status }) {
  const getStatusColor = () => {
    if (status === 'good') return 'green';
    if (status === 'warning') return 'yellow';
    return 'red';
  };

  return (
    <ProgressBar
      label={label}
      value={`${percentage}%`}
      percentage={percentage}
      color={getStatusColor()}
    />
  );
}

export default function FinancialHealthDialog({ open, onOpenChange, data = {} }) {
  const { formatCurrency } = useAppearance();

  const {
    netWorth = 0,
    monthlyIncome = 0,
    monthlyExpense = 0,
    savingsRate = 0,
    budgetUtilization = 0,
    goalProgress = 0,
    incomeVsLastMonth = 0,
    expenseVsLastMonth = 0,
  } = data;

  const netFlow = monthlyIncome - monthlyExpense;
  const isPositiveFlow = netFlow >= 0;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader className="px-6 pt-6 pb-2">
          <DialogTitle className="flex items-center gap-2 text-lg">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
              <Wallet className="h-4 w-4 text-primary" />
            </span>
            Financial Health Summary
          </DialogTitle>
          <DialogDescription>
            Overview of your current financial status and trends.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 px-6 py-2">
          {/* Key Metrics Grid */}
          <div className="grid grid-cols-2 gap-3">
            <MetricCard label="Net Worth" value={formatCurrency(netWorth)} icon={Wallet} />
            <MetricCard
              label="Monthly Net Flow"
              value={formatCurrency(Math.abs(netFlow))}
              icon={isPositiveFlow ? TrendingUp : TrendingDown}
              className={isPositiveFlow ? 'border-green-200' : 'border-red-200'}
            />
            <MetricCard
              label="Monthly Income"
              value={formatCurrency(monthlyIncome)}
              icon={ArrowUpCircle}
              trend={incomeVsLastMonth}
              trendLabel="vs last month"
            />
            <MetricCard
              label="Monthly Expenses"
              value={formatCurrency(monthlyExpense)}
              icon={ArrowDownCircle}
              trend={expenseVsLastMonth * -1}
              trendLabel="vs last month"
            />
          </div>

          {/* Health Indicators */}
          <div className="rounded-lg border bg-muted/50 p-4 space-y-3">
            <p className="text-sm font-medium">Financial Health Indicators</p>
            <HealthIndicator
              label="Savings Rate"
              percentage={Math.min(100, Math.max(0, savingsRate))}
              status={savingsRate >= 20 ? 'good' : savingsRate >= 10 ? 'warning' : 'danger'}
            />
            <HealthIndicator
              label="Budget Utilization"
              percentage={Math.min(100, Math.max(0, budgetUtilization))}
              status={
                budgetUtilization <= 80 ? 'good' : budgetUtilization <= 100 ? 'warning' : 'danger'
              }
            />
            <HealthIndicator
              label="Goal Progress"
              percentage={Math.min(100, Math.max(0, goalProgress))}
              status={goalProgress >= 50 ? 'good' : goalProgress >= 25 ? 'warning' : 'danger'}
            />
          </div>

          {/* Tips */}
          <div className="flex gap-2 rounded-lg bg-blue-50 dark:bg-blue-950/30 p-3 text-sm text-blue-700 dark:text-blue-300">
            <TrendingUp className="h-4 w-4 mt-0.5 shrink-0" />
            <div className="space-y-1">
              {savingsRate < 20 && <p>• Try to save at least 20% of your income each month</p>}
              {budgetUtilization > 90 && (
                <p>• Your budget is almost exhausted, consider reducing expenses</p>
              )}
              {goalProgress < 50 && (
                <p>• Increase contributions to reach your savings goals faster</p>
              )}
              {isPositiveFlow && savingsRate >= 20 && (
                <p>• Great job! You&apos;re on track with positive cash flow</p>
              )}
              {savingsRate >= 20 && budgetUtilization <= 90 && goalProgress >= 50 && (
                <p>• Your finances are in excellent shape!</p>
              )}
            </div>
          </div>
        </div>

        <DialogFooter className="px-6 pb-6 pt-2">
          <Button variant="outline" onClick={() => onOpenChange?.(false)}>
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
