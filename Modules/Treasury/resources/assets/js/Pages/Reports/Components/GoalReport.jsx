/**
 * Goal Report Component
 * Displays savings goals with progress and projections
 */
import { useDatatable } from '@/Hooks/useDatatable';
import EmptyState from '@Treasury/Components/EmptyState';
import QuickStatCard from '@Treasury/Components/QuickStatCard';
import { Target, TrendingUp } from 'lucide-react';
import { useMemo } from 'react';

import ProgressBar from '@/Components/Common/ProgressBar';
import DataTable from '@/Components/DataDisplay/DataTable';
import { Card, CardContent } from '@/Components/ui/card';

export default function GoalReport({
  goals,
  totals,
  formatCurrency,
  formatDate,
  referenceCurrency,
}) {
  const columns = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: 'Goal',
        cell: (info) => {
          const goal = info.row.original;
          return (
            <div className="flex items-center gap-2">
              <div
                className="w-3 h-3 rounded-full"
                style={{ backgroundColor: goal.category?.color }}
              />
              <span className="font-medium">{goal.name}</span>
              {goal.is_completed && (
                <span className="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">
                  Completed
                </span>
              )}
              {goal.is_overdue && (
                <span className="text-xs bg-rose-100 text-rose-700 px-2 py-0.5 rounded">
                  Overdue
                </span>
              )}
            </div>
          );
        },
      },
      {
        accessorKey: 'target_amount',
        header: 'Target',
        cell: (info) => formatCurrency(info.row.original.target_amount, info.row.original.currency),
      },
      {
        accessorKey: 'saved_amount',
        header: 'Saved',
        cell: (info) => formatCurrency(info.row.original.saved_amount, info.row.original.currency),
      },
      {
        accessorKey: 'percentage',
        header: 'Progress',
        cell: (info) => {
          const goal = info.row.original;
          const barColor = goal.is_completed ? 'success' : goal.category?.color || 'primary';
          return (
            <div className="flex items-center gap-2 min-w-[150px]">
              <ProgressBar value={goal.percentage} className="flex-1 space-y-0" color={barColor} />
            </div>
          );
        },
      },
      {
        accessorKey: 'deadline',
        header: 'Deadline',
        cell: (info) =>
          info.row.original.deadline ? formatDate(new Date(info.row.original.deadline)) : '-',
      },
      {
        accessorKey: 'projected_completion',
        header: 'Projected',
        cell: (info) =>
          info.row.original.projected_completion
            ? formatDate(new Date(info.row.original.projected_completion))
            : '-',
      },
    ],
    [formatCurrency, formatDate]
  );

  const { tableProps } = useDatatable({
    data: goals || [],
    columns,
    serverSide: false,
    pageSize: 20,
  });

  return (
    <>
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <QuickStatCard
          title="Total Target"
          value={formatCurrency(totals?.target || 0, referenceCurrency)}
          icon={Target}
          color="blue"
        />
        <QuickStatCard
          title="Total Saved"
          value={formatCurrency(totals?.saved || 0, referenceCurrency)}
          icon={TrendingUp}
          color="green"
        />
        <QuickStatCard
          title="Active Goals"
          value={totals?.active || 0}
          icon={Target}
          color="orange"
        />
        <QuickStatCard
          title="Completed"
          value={totals?.completed || 0}
          icon={Target}
          color="green"
        />
      </div>

      {goals && goals.length > 0 ? (
        <DataTable {...tableProps} title="Savings Goals" showCard={true} />
      ) : (
        <Card>
          <CardContent className="p-8">
            <EmptyState
              icon={Target}
              message="No goals found. Create savings goals to track progress."
            />
          </CardContent>
        </Card>
      )}
    </>
  );
}
