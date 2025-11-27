/**
 * Form card component for wrapping form content in a consistent card layout
 * Supports title, description, and optional icon with customizable styling
 */
import { cn } from '@/Utils/utils';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function FormCard({
  title,
  description,
  children,
  className,
  headerClassName,
  contentClassName,
  icon: Icon,
  titleClassName,
}) {
  return (
    <Card className={cn(className)}>
      <CardHeader className={cn(headerClassName)}>
        <div className="flex items-center gap-2">
          {Icon && <Icon className="h-5 w-5 text-muted-foreground" />}
          <div className="flex-1 space-y-2">
            <CardTitle className={cn(titleClassName)}>{title}</CardTitle>
            {description && <CardDescription>{description}</CardDescription>}
          </div>
        </div>
      </CardHeader>
      <CardContent className={cn(contentClassName)}>{children}</CardContent>
    </Card>
  );
}
