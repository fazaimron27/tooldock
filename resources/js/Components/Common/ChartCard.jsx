import { cn } from '@/Utils/utils';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChartContainer } from '@/Components/ui/chart';

/**
 * Generic chart card component that wraps a chart in a Card with consistent styling
 * @param {object} props
 * @param {string} props.title - Chart title
 * @param {string} props.description - Chart description
 * @param {React.ReactNode} props.children - Chart component (BarChart, AreaChart, LineChart, etc.)
 * @param {object} props.config - Chart configuration for ChartContainer
 * @param {string} props.className - Additional CSS classes for the card
 * @param {string} props.height - Chart height class (default: "h-[300px]")
 * @param {string} props.contentClassName - Additional CSS classes for CardContent
 */
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
