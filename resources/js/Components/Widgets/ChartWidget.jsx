/**
 * Chart Widget component for displaying various chart types
 * Supports bar, area, and line charts based on widget data
 */
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Line,
  LineChart,
  XAxis,
  YAxis,
} from 'recharts';

import ChartCard from '@/Components/Common/ChartCard';
import { ChartTooltip, ChartTooltipContent } from '@/Components/ui/chart';

/**
 * Render a chart widget
 *
 * @param {Object} widget - The widget object from the registry
 * @param {string} widget.title - Widget title
 * @param {string|null} widget.description - Optional description
 * @param {array} widget.data - Chart data array
 * @param {string} widget.chartType - Chart type ('bar', 'area', 'line')
 */
export default function ChartWidget({ widget }) {
  if (!widget) {
    console.error('ChartWidget: widget prop is null or undefined');
    return null;
  }

  const title = widget.title || 'Untitled Chart';
  const description = widget.description ?? null;
  const data = Array.isArray(widget.data) ? widget.data : [];
  const chartType = widget.chartType || 'bar';

  const defaultConfig = {
    value: {
      label: 'Value',
      color: 'hsl(var(--chart-1))',
    },
  };

  const config = widget.config || defaultConfig;

  const renderChart = () => {
    switch (chartType) {
      case 'bar':
        return (
          <BarChart data={data}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <ChartTooltip content={<ChartTooltipContent />} />
            <Bar dataKey="value" fill="var(--color-value)" radius={[8, 8, 0, 0]} />
          </BarChart>
        );

      case 'area':
        return (
          <AreaChart data={data}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey={widget.xAxisKey || 'month'} />
            <YAxis />
            <ChartTooltip content={<ChartTooltipContent />} />
            {widget.dataKeys?.map((key) => (
              <Area
                key={key}
                type="monotone"
                dataKey={key}
                stroke={`var(--color-${key})`}
                fill={`var(--color-${key})`}
                fillOpacity={0.6}
              />
            )) || (
              <>
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
              </>
            )}
          </AreaChart>
        );

      case 'line':
        return (
          <LineChart data={data}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey={widget.xAxisKey || 'name'} />
            <YAxis />
            <ChartTooltip content={<ChartTooltipContent />} />
            <Line
              type="monotone"
              dataKey="value"
              stroke="var(--color-value)"
              strokeWidth={2}
              dot={{ r: 4 }}
            />
          </LineChart>
        );

      default:
        return null;
    }
  };

  if (data.length === 0) {
    return (
      <ChartCard title={title} description={description} config={config}>
        <div className="flex items-center justify-center h-64 text-muted-foreground">
          <p>No data available</p>
        </div>
      </ChartCard>
    );
  }

  return (
    <ChartCard title={title} description={description} config={config}>
      {renderChart()}
    </ChartCard>
  );
}
