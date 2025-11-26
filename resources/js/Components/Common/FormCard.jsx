import { cn } from '@/Utils/utils';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Reusable card component for forms with consistent styling
 * @param {object} props
 * @param {string} props.title - Card title
 * @param {string} props.description - Optional description text
 * @param {React.ReactNode} props.children - Form content
 * @param {string} props.className - Additional CSS classes for the card
 * @param {string} props.headerClassName - Additional CSS classes for the header
 * @param {string} props.contentClassName - Additional CSS classes for the content
 * @param {string} props.titleClassName - Additional CSS classes for the title
 * @param {React.ComponentType} props.icon - Optional icon component
 */
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
