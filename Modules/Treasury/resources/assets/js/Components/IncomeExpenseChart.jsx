/**
 * Income vs Expenses Area Chart
 * Displays 6-month trend of income vs expenses
 * Uses Recharts with shadcn/ui ChartContainer
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { useMemo } from 'react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

const chartConfig = {
  income: {
    label: 'Income',
    color: 'hsl(var(--chart-2))',
  },
  expense: {
    label: 'Expenses',
    color: 'hsl(var(--chart-1))',
  },
};

export default function IncomeExpenseChart({ data = [], className = '' }) {
  const { formatCurrency } = useAppearance();

  // Ensure we have valid data
  const chartData = useMemo(() => {
    if (!data || data.length === 0) {
      return [];
    }
    return data.map((item) => ({
      month: item.month,
      year: item.year,
      label: `${item.month} ${item.year}`,
      income: parseFloat(item.income) || 0,
      expense: parseFloat(item.expense) || 0,
    }));
  }, [data]);

  if (chartData.length === 0) {
    return null;
  }

  return (
    <ChartContainer config={chartConfig} className={`h-[200px] ${className}`}>
      <AreaChart data={chartData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
        <defs>
          <linearGradient id="incomeGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="var(--color-income)" stopOpacity={0.6} />
            <stop offset="95%" stopColor="var(--color-income)" stopOpacity={0.1} />
          </linearGradient>
          <linearGradient id="expenseGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="var(--color-expense)" stopOpacity={0.6} />
            <stop offset="95%" stopColor="var(--color-expense)" stopOpacity={0.1} />
          </linearGradient>
        </defs>
        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
        <XAxis
          dataKey="month"
          tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 12 }}
          tickLine={false}
          axisLine={false}
        />
        <YAxis
          tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 12 }}
          tickLine={false}
          axisLine={false}
          tickFormatter={(value) => {
            if (value >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
            if (value >= 1000) return `${(value / 1000).toFixed(0)}K`;
            return value;
          }}
        />
        <ChartTooltip
          content={
            <ChartTooltipContent
              labelFormatter={(value, payload) => {
                const data = payload?.[0]?.payload;
                return data?.label || value;
              }}
              formatter={(value, name) => (
                <div className="flex items-center justify-between gap-4 w-full min-w-[160px]">
                  <span className="text-muted-foreground capitalize">{name}</span>
                  <span className="font-mono font-medium text-right">{formatCurrency(value)}</span>
                </div>
              )}
            />
          }
        />
        <Area
          type="monotone"
          dataKey="income"
          stroke="var(--color-income)"
          strokeWidth={2}
          fill="url(#incomeGradient)"
        />
        <Area
          type="monotone"
          dataKey="expense"
          stroke="var(--color-expense)"
          strokeWidth={2}
          fill="url(#expenseGradient)"
        />
      </AreaChart>
    </ChartContainer>
  );
}
