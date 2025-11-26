import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import ChartCard from '@/Components/Common/ChartCard';
import { ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

/**
 * Sales chart card component displaying monthly sales performance
 * @param {object} props
 * @param {array} props.data - Chart data array with { name, value } structure
 * @param {string} props.className - Additional CSS classes
 */
export default function SalesChartCard({ data = [], className }) {
  const config = {
    value: {
      label: 'Sales',
      color: 'hsl(var(--chart-1))',
    },
  };

  return (
    <ChartCard
      title="Sales Overview"
      description="Monthly sales performance"
      config={config}
      className={className}
    >
      <BarChart data={data}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="name" />
        <YAxis />
        <ChartTooltip content={<ChartTooltipContent />} />
        <Bar dataKey="value" fill="var(--color-value)" radius={[8, 8, 0, 0]} />
      </BarChart>
    </ChartCard>
  );
}
