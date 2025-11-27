/**
 * Generic metric card component for displaying stats, info, or any content
 * Supports custom header, footer, and icon with flexible content area
 */
import { cn } from '@/Utils/utils';

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';

export default function MetricCard({
  title,
  description,
  children,
  header,
  footer,
  icon: Icon,
  className,
  headerClassName,
  contentClassName,
}) {
  return (
    <Card className={cn(className)}>
      {header ? (
        <div className={cn('p-6', headerClassName)}>{header}</div>
      ) : title || Icon || description ? (
        <CardHeader
          className={cn(
            'flex flex-row items-center justify-between space-y-0 pb-2',
            headerClassName
          )}
        >
          <div className="space-y-1.5">
            {title && <CardTitle className="text-sm font-medium">{title}</CardTitle>}
            {description && <CardDescription>{description}</CardDescription>}
          </div>
          {Icon && <Icon className="h-4 w-4 text-muted-foreground" />}
        </CardHeader>
      ) : null}

      {children && <CardContent className={cn(contentClassName)}>{children}</CardContent>}

      {footer && <CardFooter>{footer}</CardFooter>}
    </Card>
  );
}
