/**
 * Revenue chart card displaying revenue vs expenses comparison over time
 * Uses area chart to visualize financial trends
 */
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import ChartCard from '@/Components/Common/ChartCard';
import { ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

export default function RevenueChartCard({ data = [], className }) {
  const config = {
    revenue: {
      label: 'Revenue',
      color: 'hsl(var(--chart-1))',
    },
    expenses: {
      label: 'Expenses',
      color: 'hsl(var(--chart-2))',
    },
  };

  return (
    <ChartCard
      title="Revenue & Expenses"
      description="Revenue vs expenses comparison"
      config={config}
      className={className}
    >
      <AreaChart data={data}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="month" />
        <YAxis />
        <ChartTooltip content={<ChartTooltipContent />} />
        <Area
          type="monotone"
          dataKey="revenue"
          stroke="var(--color-revenue)"
          fill="var(--color-revenue)"
          fillOpacity={0.6}
        />
        <Area
          type="monotone"
          dataKey="expenses"
          stroke="var(--color-expenses)"
          fill="var(--color-expenses)"
          fillOpacity={0.6}
        />
      </AreaChart>
    </ChartCard>
  );
}
