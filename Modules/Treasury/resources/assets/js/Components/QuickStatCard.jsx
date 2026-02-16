/**
 * QuickStatCard - Stat card for displaying metrics with icons
 * Shared component for Treasury pages (Dashboard, Budgets, Transactions, etc.)
 * Enhanced with more color variants and styling options
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { cn } from '@/Utils/utils';

import { Card, CardContent } from '@/Components/ui/card';

const colorVariants = {
  blue: {
    bg: 'bg-blue-100 dark:bg-blue-900/30',
    icon: 'text-blue-600 dark:text-blue-400',
    value: 'text-blue-600 dark:text-blue-400',
  },
  amber: {
    bg: 'bg-amber-100 dark:bg-amber-900/30',
    icon: 'text-amber-600 dark:text-amber-400',
    value: 'text-amber-600 dark:text-amber-400',
  },
  green: {
    bg: 'bg-green-100 dark:bg-green-900/30',
    icon: 'text-green-600 dark:text-green-400',
    value: 'text-green-600 dark:text-green-400',
  },
  emerald: {
    bg: 'bg-emerald-500/10',
    icon: 'text-emerald-500',
    value: 'text-emerald-600 dark:text-emerald-400',
  },
  red: {
    bg: 'bg-red-100 dark:bg-red-900/30',
    icon: 'text-red-600 dark:text-red-400',
    value: 'text-red-600 dark:text-red-400',
  },
  rose: {
    bg: 'bg-rose-500/10',
    icon: 'text-rose-500',
    value: 'text-rose-600 dark:text-rose-400',
  },
  purple: {
    bg: 'bg-purple-100 dark:bg-purple-900/30',
    icon: 'text-purple-600 dark:text-purple-400',
    value: 'text-purple-600 dark:text-purple-400',
  },
  primary: {
    bg: 'bg-primary/10',
    icon: 'text-primary',
    value: 'text-foreground',
  },
  neutral: {
    bg: 'bg-muted',
    icon: 'text-muted-foreground',
    value: 'text-foreground',
  },
};

export default function QuickStatCard({
  title,
  value,
  subtitle,
  icon: Icon,
  color = 'blue',
  valueClassName,
  className,
  formatAsCurrency = false,
  prefix,
  compact = false,
}) {
  const { formatCurrency } = useAppearance();
  const colors = colorVariants[color] || colorVariants.blue;
  const displayValue =
    formatAsCurrency && typeof value === 'number' ? formatCurrency(Math.abs(value)) : value;

  if (compact) {
    return (
      <div
        className={cn(
          'flex items-center gap-4 p-4 rounded-xl bg-card border border-border/50',
          'transition-all duration-200 hover:border-border hover:shadow-sm',
          className
        )}
      >
        {Icon && (
          <div
            className={cn(
              'w-11 h-11 rounded-xl flex items-center justify-center shrink-0',
              colors.bg
            )}
          >
            <Icon className={cn('w-5 h-5', colors.icon)} />
          </div>
        )}
        <div className="min-w-0 flex-1">
          <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-0.5">
            {title}
          </p>
          <p
            className={cn(
              'font-bold text-lg tabular-nums tracking-tight truncate',
              colors.value,
              valueClassName
            )}
          >
            {prefix}
            {displayValue}
          </p>
          {subtitle && <p className="text-xs text-muted-foreground">{subtitle}</p>}
        </div>
      </div>
    );
  }

  return (
    <Card className={cn('transition-all duration-200 hover:shadow-sm', className)}>
      <CardContent className="pt-4">
        <div className="flex items-center gap-3">
          {Icon && (
            <div className={cn('p-2 rounded-lg', colors.bg)}>
              <Icon className={cn('w-5 h-5', colors.icon)} />
            </div>
          )}
          <div className="min-w-0 flex-1">
            <p className="text-sm text-muted-foreground">{title}</p>
            <p
              className={cn(
                'text-2xl font-bold tabular-nums truncate',
                valueClassName || colors.value
              )}
            >
              {prefix}
              {displayValue}
            </p>
            {subtitle && <p className="text-xs text-muted-foreground">{subtitle}</p>}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
