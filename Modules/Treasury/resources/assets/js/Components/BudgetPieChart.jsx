/**
 * Budget Pie Chart component
 * Displays a donut chart showing budget allocation by category
 * Uses Recharts with shadcn/ui ChartContainer
 *
 * When budgets have different currencies, uses converted_amount (pre-converted
 * to reference currency by the backend) for accurate proportional visualization.
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { useMemo } from 'react';
import { Cell, Label, Pie, PieChart } from 'recharts';

import {
  ChartContainer,
  ChartLegend,
  ChartLegendContent,
  ChartTooltip,
  ChartTooltipContent,
} from '@/Components/ui/chart';

// Default colors for pie chart segments
const CHART_COLORS = [
  'hsl(var(--chart-1))',
  'hsl(var(--chart-2))',
  'hsl(var(--chart-3))',
  'hsl(var(--chart-4))',
  'hsl(var(--chart-5))',
  '#8884d8',
  '#82ca9d',
  '#ffc658',
  '#ff7c43',
  '#a05195',
];

/**
 * Prepare chart data from budget categories
 * Uses converted_amount for pie chart values to ensure accurate proportions
 * across different currencies. The converted_amount is pre-converted to
 * the user's reference currency by the backend.
 */
function prepareChartData(budgets) {
  if (!budgets || budgets.length === 0) {
    return { chartData: [], chartConfig: {} };
  }

  const chartData = budgets.map((budget, index) => ({
    // Category name serves as budget identifier (budget.category is the category name from report)
    name: budget.category?.name || budget.category || `Budget ${index + 1}`,
    // Use converted_amount for accurate multi-currency comparison in pie chart
    // Falls back to amount for backward compatibility
    value: parseFloat(budget.converted_amount ?? budget.amount) || 0,
    spent: parseFloat(budget.converted_spent ?? budget.spent) || 0,
    // Keep original values for tooltip display
    originalAmount: parseFloat(budget.amount) || 0,
    currency: budget.currency,
    fill: budget.category?.color || CHART_COLORS[index % CHART_COLORS.length],
  }));

  const chartConfig = budgets.reduce((acc, budget, index) => {
    // Category name serves as budget identifier
    const categoryName = budget.category?.name || budget.category || `budget_${index}`;
    const key = categoryName.toLowerCase().replace(/\s+/g, '_');
    acc[key] = {
      label: categoryName,
      color:
        budget.category?.color ||
        budget.category_color ||
        budget.category_color ||
        CHART_COLORS[index % CHART_COLORS.length],
    };
    return acc;
  }, {});

  return { chartData, chartConfig };
}

export default function BudgetPieChart({ budgets = [], className = '' }) {
  const { formatCurrency } = useAppearance();
  const { chartData, chartConfig } = useMemo(() => prepareChartData(budgets), [budgets]);

  if (chartData.length === 0) {
    return null;
  }

  return (
    <ChartContainer config={chartConfig} className={`h-[200px] ${className}`}>
      <PieChart>
        <ChartTooltip
          content={
            <ChartTooltipContent
              formatter={(value, name) => (
                <div className="flex items-center justify-between gap-4 min-w-[120px]">
                  <span className="text-muted-foreground">{name}</span>
                  <span className="font-mono font-medium">{formatCurrency(value)}</span>
                </div>
              )}
            />
          }
        />
        <Pie
          data={chartData}
          dataKey="value"
          nameKey="name"
          cx="50%"
          cy="50%"
          innerRadius={50}
          outerRadius={70}
          strokeWidth={2}
          stroke="hsl(var(--background))"
        >
          {chartData.map((entry, index) => (
            <Cell key={`cell-${index}`} fill={entry.fill} />
          ))}
          <Label
            content={({ viewBox }) => {
              if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                return (
                  <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle" dominantBaseline="middle">
                    <tspan>{budgets.length}</tspan>
                    <tspan
                      x={viewBox.cx}
                      y={(viewBox.cy || 0) + 16}
                      className="fill-muted-foreground text-xs"
                    >
                      Budgets
                    </tspan>
                  </text>
                );
              }
            }}
          />
        </Pie>
      </PieChart>
    </ChartContainer>
  );
}
