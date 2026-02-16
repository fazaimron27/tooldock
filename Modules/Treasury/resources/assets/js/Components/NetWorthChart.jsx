/**
 * Net Worth Growth Line Chart
 * Displays net worth trend over time (monthly snapshots)
 * Uses Recharts with shadcn/ui ChartContainer
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { useMemo } from 'react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

const chartConfig = {
  netWorth: {
    label: 'Net Worth',
    color: 'hsl(var(--chart-3))',
  },
};

export default function NetWorthChart({ data = [], className = '' }) {
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
      netWorth: parseFloat(item.netWorth || item.net_worth || item.total) || 0,
    }));
  }, [data]);

  if (chartData.length === 0) {
    return null;
  }

  // Calculate min/max for Y axis domain
  const minValue = Math.min(...chartData.map((d) => d.netWorth));
  const maxValue = Math.max(...chartData.map((d) => d.netWorth));
  const padding = (maxValue - minValue) * 0.1 || maxValue * 0.1;

  return (
    <ChartContainer config={chartConfig} className={`h-[200px] ${className}`}>
      <AreaChart data={chartData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
        <defs>
          <linearGradient id="netWorthGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="5%" stopColor="var(--color-netWorth)" stopOpacity={0.4} />
            <stop offset="95%" stopColor="var(--color-netWorth)" stopOpacity={0.05} />
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
          domain={[Math.max(0, minValue - padding), maxValue + padding]}
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
              formatter={(value) => (
                <div className="flex items-center justify-between gap-4 min-w-[120px]">
                  <span className="text-muted-foreground">Net Worth</span>
                  <span className="font-mono font-medium">{formatCurrency(value)}</span>
                </div>
              )}
            />
          }
        />
        <Area
          type="monotone"
          dataKey="netWorth"
          stroke="var(--color-netWorth)"
          strokeWidth={2}
          fill="url(#netWorthGradient)"
          dot={{ r: 3, fill: 'var(--color-netWorth)' }}
          activeDot={{ r: 5, fill: 'var(--color-netWorth)' }}
        />
      </AreaChart>
    </ChartContainer>
  );
}
