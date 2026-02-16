/**
 * Budgets Index page - List all budgets with health status
 * Supports Template + Instance model with month navigation
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { cn } from '@/Utils/utils';
import { HealthStatusCard } from '@Treasury/Components/BudgetStatusIndicator';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import { Link, router } from '@inertiajs/react';
import {
  AlertTriangle,
  ArrowRightLeft,
  Calendar,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  DollarSign,
  MoreVertical,
  Pencil,
  PieChart,
  Plus,
  Trash2,
  TrendingDown,
  TrendingUp,
} from 'lucide-react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const statusConfig = {
  overbudget: {
    icon: AlertTriangle,
    iconClass: 'text-red-500',
    color: 'destructive',
    textClass: 'text-red-500',
  },
  warning: {
    icon: TrendingUp,
    iconClass: 'text-amber-500',
    color: 'warning',
    textClass: 'text-amber-500',
  },
  safe: {
    icon: CheckCircle2,
    iconClass: 'text-emerald-500',
    color: 'success',
    textClass: 'text-emerald-500',
  },
};

export default function Index({
  _budgets,
  report,
  summary,
  currentMonth,
  currentPeriod,
  navigation,
}) {
  const { formatCurrency } = useAppearance();
  const [deleteBudget, setDeleteBudget] = useState(null);

  const handleDelete = () => {
    if (deleteBudget) {
      router.delete(route('treasury.budgets.destroy', deleteBudget.id), {
        onSuccess: () => setDeleteBudget(null),
      });
    }
  };

  const navigateToMonth = (month, year) => {
    router.get(route('treasury.budgets.index'), { month, year }, { preserveScroll: true });
  };

  const goToCurrentMonth = () => {
    const now = new Date();
    navigateToMonth(now.getMonth() + 1, now.getFullYear());
  };

  return (
    <PageShell
      title="Budgets"
      description={`Spending limits for ${currentMonth}`}
      actions={
        <Link href={route('treasury.budgets.create')}>
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Add Budget Template
          </Button>
        </Link>
      }
    >
      {/* Month Navigation */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="icon"
            onClick={() => navigateToMonth(navigation?.previous?.month, navigation?.previous?.year)}
          >
            <ChevronLeft className="w-4 h-4" />
          </Button>
          <div className="flex items-center gap-2 px-4 py-2 bg-muted/50 rounded-lg">
            <Calendar className="w-4 h-4 text-muted-foreground" />
            <span className="font-semibold text-lg">{currentMonth}</span>
          </div>
          <Button
            variant="outline"
            size="icon"
            onClick={() => navigateToMonth(navigation?.next?.month, navigation?.next?.year)}
          >
            <ChevronRight className="w-4 h-4" />
          </Button>
        </div>
        {!navigation?.isCurrentMonth && (
          <Button variant="ghost" size="sm" onClick={goToCurrentMonth}>
            Go to Current Month
          </Button>
        )}
      </div>

      {/* Summary Stats - 3 columns */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <QuickStatCard
          title="Total Budgeted"
          value={formatCurrency(summary?.total_budgeted || 0)}
          subtitle={
            summary?.total_rollover > 0
              ? `+${formatCurrency(summary.total_rollover)} rollover`
              : null
          }
          icon={DollarSign}
          color="blue"
        />
        <QuickStatCard
          title="Total Spent"
          value={formatCurrency(summary?.total_spent || 0)}
          icon={TrendingDown}
          color="red"
          valueClassName="text-xl text-red-600"
        />
        <QuickStatCard
          title="Remaining"
          value={formatCurrency(summary?.total_remaining || 0)}
          icon={TrendingUp}
          color="green"
          valueClassName="text-xl text-green-600"
        />
      </div>

      {/* Health Status - Full width */}
      <Card className="mb-6">
        <CardContent className="pt-4">
          <p className="text-sm text-muted-foreground mb-3">Health Status</p>
          <HealthStatusCard
            safeCount={summary?.safe_count || 0}
            warningCount={summary?.warning_count || 0}
            overCount={summary?.overbudget_count || 0}
            monthLabel={currentMonth}
          />
        </CardContent>
      </Card>

      {report && report.length > 0 ? (
        <div className="flex flex-wrap gap-4">
          {report.map((budget) => {
            const config = statusConfig[budget.status] || statusConfig.safe;
            const StatusIcon = config.icon;
            const percentage = Math.min(budget.raw_health, 100);
            const hasRollover = budget.rollover > 0;

            return (
              <Card
                key={budget.id}
                className={cn(
                  'group relative overflow-hidden transition-all hover:shadow-md w-full sm:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.67rem)]',
                  budget.status === 'overbudget' && 'ring-1 ring-red-200 dark:ring-red-900'
                )}
              >
                <CardContent className="p-5">
                  {/* Header: Category & Actions */}
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2">
                        <div
                          className="w-3 h-3 rounded-full ring-2 ring-offset-2 ring-offset-background flex-shrink-0"
                          style={{
                            backgroundColor: budget.category_color || '#6b7280',
                            boxShadow: `0 0 0 2px ${budget.category_color || '#6b7280'}20`,
                          }}
                        />
                        <span className="font-semibold text-lg truncate">{budget.category}</span>
                        {budget.rollover_enabled && (
                          <Badge variant="secondary" className="text-xs px-1.5 py-0 flex-shrink-0">
                            <ArrowRightLeft className="w-3 h-3 mr-1" />
                            Rollover
                          </Badge>
                        )}
                      </div>
                    </div>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8"
                        >
                          <MoreVertical className="w-4 h-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                          <Link
                            href={route('treasury.budgets.edit', {
                              budget: budget.id,
                              month: currentPeriod?.month,
                              year: currentPeriod?.year,
                            })}
                          >
                            <Pencil className="w-4 h-4 mr-2" />
                            Edit This Month
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                          <Link href={route('treasury.budgets.edit', budget.id)}>
                            <Pencil className="w-4 h-4 mr-2" />
                            Edit Budget Template
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          className="text-destructive"
                          onClick={() => setDeleteBudget(budget)}
                        >
                          <Trash2 className="w-4 h-4 mr-2" />
                          Delete
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>

                  {/* Progress Circle & Amount */}
                  <div className="flex items-center gap-4">
                    {/* Circular Progress */}
                    <div className="relative w-16 h-16 flex-shrink-0">
                      <svg className="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle
                          className="text-muted/30"
                          strokeWidth="3"
                          stroke="currentColor"
                          fill="none"
                          r="15.5"
                          cx="18"
                          cy="18"
                        />
                        <circle
                          className={cn(
                            'transition-all duration-500',
                            budget.status === 'safe' && 'text-emerald-500',
                            budget.status === 'warning' && 'text-amber-500',
                            budget.status === 'overbudget' && 'text-red-500'
                          )}
                          strokeWidth="3"
                          strokeLinecap="round"
                          stroke="currentColor"
                          fill="none"
                          r="15.5"
                          cx="18"
                          cy="18"
                          strokeDasharray={`${percentage} 100`}
                        />
                      </svg>
                      <div className="absolute inset-0 flex items-center justify-center">
                        <span className={cn('text-sm font-bold', config.textClass)}>
                          {budget.raw_health}%
                        </span>
                      </div>
                    </div>

                    {/* Amount Info */}
                    <div className="flex-1 space-y-1">
                      <div className="flex items-center gap-1.5">
                        <StatusIcon className={cn('w-4 h-4', config.iconClass)} />
                        <span className={cn('text-sm font-medium', config.textClass)}>
                          {budget.status === 'safe' && 'On Track'}
                          {budget.status === 'warning' &&
                            (budget.health >= 100 ? 'Fully Used' : 'Approaching Limit')}
                          {budget.status === 'overbudget' && 'Over Budget'}
                        </span>
                      </div>
                      <p className="text-lg font-bold">
                        {formatCurrency(budget.spent, budget.currency)}
                        <span className="text-sm font-normal text-muted-foreground">
                          {' '}
                          / {formatCurrency(budget.total_limit || budget.limit, budget.currency)}
                        </span>
                      </p>
                      {hasRollover && (
                        <p className="text-xs text-blue-500">
                          +{formatCurrency(budget.rollover, budget.currency)} from last month
                        </p>
                      )}
                      {budget.remaining > 0 && budget.status !== 'overbudget' && (
                        <p className="text-xs text-muted-foreground">
                          {formatCurrency(budget.remaining, budget.currency)} remaining
                        </p>
                      )}
                      {budget.status === 'overbudget' && (
                        <p className="text-xs text-red-500 font-medium">
                          {formatCurrency(
                            budget.spent - (budget.total_limit || budget.limit),
                            budget.currency
                          )}{' '}
                          over limit
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Description indicator */}
                  {budget.description && (
                    <p className="text-xs text-muted-foreground mt-3 italic truncate">
                      📝 {budget.description}
                    </p>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>
      ) : (
        <Card>
          <CardContent className="py-12">
            <EmptyState icon={PieChart} message="Create budget template to track your spending." />
          </CardContent>
        </Card>
      )}

      <ConfirmDialog
        isOpen={!!deleteBudget}
        onCancel={() => setDeleteBudget(null)}
        title="Delete Budget"
        message={`Are you sure you want to delete the budget for "${deleteBudget?.category}"? This will delete all historical data.`}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
