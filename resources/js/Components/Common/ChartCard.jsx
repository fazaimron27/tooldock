/**
 * Chart card component that wraps chart components in a consistent card layout
 * Supports various chart types (BarChart, AreaChart, LineChart) with configurable styling
 */
import { cn } from '@/Utils/utils';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer } from '@/Components/ui/chart';

export default function ChartCard({
  title,
  description,
  children,
  config,
  className,
  height = 'h-[300px]',
  contentClassName,
}) {
  return (
    <Card className={cn(className)}>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent className={cn('overflow-hidden', contentClassName)}>
        <ChartContainer config={config} className={cn('w-full', height)}>
          {children}
        </ChartContainer>
      </CardContent>
    </Card>
  );
}
