import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';

import ChartCard from '@/Components/Common/ChartCard';
import { ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

/**
 * Growth trend chart card component displaying user growth over time
 * @param {object} props
 * @param {array} props.data - Chart data array with { name, value } structure
 * @param {string} props.className - Additional CSS classes
 */
export default function GrowthTrendChartCard({ data = [], className }) {
  const config = {
    users: {
      label: 'Users',
      color: 'hsl(var(--chart-3))',
    },
  };

  return (
    <ChartCard
      title="Growth Trend"
      description="User growth over the last 7 months"
      config={config}
      className={className}
    >
      <LineChart data={data}>
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="name" />
        <YAxis />
        <ChartTooltip content={<ChartTooltipContent />} />
        <Line
          type="monotone"
          dataKey="value"
          stroke="var(--color-users)"
          strokeWidth={2}
          dot={{ r: 4 }}
        />
      </LineChart>
    </ChartCard>
  );
}
