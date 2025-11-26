import { cn } from '@/Utils/utils';

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';

/**
 * Generic metric/info card component that can be used for stats, info, or any content
 * @param {object} props
 * @param {string} props.title - Card title
 * @param {string} props.description - Optional description text
 * @param {React.ReactNode} props.children - Main content area
 * @param {React.ReactNode} props.header - Custom header content (overrides title/description)
 * @param {React.ReactNode} props.footer - Footer content
 * @param {React.ComponentType} props.icon - Optional icon component from lucide-react
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.headerClassName - Additional CSS classes for header
 * @param {string} props.contentClassName - Additional CSS classes for content
 */
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
