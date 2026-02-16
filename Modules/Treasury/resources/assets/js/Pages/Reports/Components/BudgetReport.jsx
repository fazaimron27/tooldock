/**
 * Budget Report Component
 * Displays budget vs actual spending comparison with usage indicators
 */
import { useDatatable } from '@/Hooks/useDatatable';
import { cn } from '@/Utils/utils';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import { PieChart, TrendingDown, TrendingUp } from 'lucide-react';
import { useMemo } from 'react';

import ProgressBar from '@/Components/Common/ProgressBar';
import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent } from '@/Components/ui/card';

export default function BudgetReport({ budgets, totals, formatCurrency, referenceCurrency }) {
  const columns = useMemo(
    () => [
      {
        accessorKey: 'category',
        header: 'Category',
        cell: (info) => {
          const budget = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <div
                className="w-3 h-3 rounded-full"
                style={{ backgroundColor: budget.category?.color }}
              />
              <span className="font-medium">{budget.category?.name || 'Uncategorized'}</span>
            </div>
          );
        },
      },
      {
        accessorKey: 'budgeted',
        header: 'Budgeted',
        cell: (info) => formatCurrency(info.row.original.budgeted, info.row.original.currency),
      },
      {
        accessorKey: 'spent',
        header: 'Spent',
        cell: (info) => formatCurrency(info.row.original.spent, info.row.original.currency),
      },
      {
        accessorKey: 'remaining',
        header: 'Remaining',
        cell: (info) => {
          const budget = info.row.original;
          return (
            <span
              className={cn(budget.is_over ? 'text-rose-600' : 'text-emerald-600', 'font-medium')}
            >
              {formatCurrency(budget.remaining, budget.currency)}
            </span>
          );
        },
      },
      {
        accessorKey: 'percentage',
        header: 'Usage',
        cell: (info) => {
          const budget = info.row.original;
          const barColor = budget.is_over ? 'destructive' : budget.category?.color || 'primary';
          return (
            <div className="flex items-center gap-2 min-w-[150px]">
              <ProgressBar
                value={budget.percentage}
                className="flex-1 space-y-0"
                color={barColor}
              />
            </div>
          );
        },
      },
    ],
    [formatCurrency]
  );

  const { tableProps } = useDatatable({
    data: budgets || [],
    columns,
    serverSide: false,
    pageSize: 20,
  });

  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <QuickStatCard
          title="Total Budgeted"
          value={formatCurrency(totals?.budgeted || 0, referenceCurrency)}
          icon={PieChart}
          color="blue"
        />
        <QuickStatCard
          title="Total Spent"
          value={formatCurrency(totals?.spent || 0, referenceCurrency)}
          icon={TrendingDown}
          color="red"
        />
        <QuickStatCard
          title="Remaining"
          value={formatCurrency(totals?.remaining || 0, referenceCurrency)}
          icon={TrendingUp}
          color="green"
        />
      </div>

      {budgets && budgets.length > 0 ? (
        <DataTable {...tableProps} title="Budget vs Actual" showCard={true} />
      ) : (
        <Card>
          <CardContent className="p-8">
            <EmptyState
              icon={PieChart}
              message="No budgets found. Create budgets to track spending."
            />
          </CardContent>
        </Card>
      )}
    </>
  );
}
