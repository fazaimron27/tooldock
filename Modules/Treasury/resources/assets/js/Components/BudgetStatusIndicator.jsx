/**
 * Budget status indicator components
 * Shows health status badges for budget tracking
 */
import { cn } from '@/Utils/utils';
import { AlertTriangle, CheckCircle2, TrendingUp } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';

/**
 * Compact badge-based status indicator
 */
export default function BudgetStatusIndicator({ status, count, className }) {
  const config = {
    safe: {
      icon: CheckCircle2,
      label: 'On Track',
      badgeClass:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 hover:bg-emerald-100',
    },
    warning: {
      icon: TrendingUp,
      label: 'Warning',
      badgeClass:
        'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 hover:bg-amber-100',
    },
    over: {
      icon: AlertTriangle,
      label: 'Over Budget',
      badgeClass: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300 hover:bg-red-100',
    },
  }[status] || {
    icon: CheckCircle2,
    label: 'On Track',
    badgeClass: 'bg-emerald-100 text-emerald-700',
  };

  return (
    <Badge variant="secondary" className={cn(config.badgeClass, className)}>
      <config.icon className="w-3 h-3 mr-1" />
      {count} {config.label}
    </Badge>
  );
}

/**
 * Enhanced horizontal status group with badges
 */
export function BudgetStatusGroup({ safeCount = 0, warningCount = 0, overCount = 0, className }) {
  return (
    <div className={cn('flex flex-wrap gap-2 justify-center', className)}>
      <BudgetStatusIndicator status="safe" count={safeCount} />
      <BudgetStatusIndicator status="warning" count={warningCount} />
      <BudgetStatusIndicator status="over" count={overCount} />
    </div>
  );
}

/**
 * Health Status Card - Full width visual display
 * Shows 3 status boxes in a horizontal grid with icons and counts
 */
export function HealthStatusCard({
  safeCount = 0,
  warningCount = 0,
  overCount = 0,
  monthLabel = 'this month',
  className,
}) {
  const statuses = [
    {
      icon: CheckCircle2,
      count: safeCount,
      label: 'On Track',
      colorClass: {
        bg: 'bg-emerald-100 dark:bg-emerald-950/50',
        icon: 'text-emerald-600 dark:text-emerald-400',
        ring: 'ring-emerald-500/20',
      },
    },
    {
      icon: TrendingUp,
      count: warningCount,
      label: 'Warning',
      colorClass: {
        bg: 'bg-amber-100 dark:bg-amber-950/50',
        icon: 'text-amber-600 dark:text-amber-400',
        ring: 'ring-amber-500/20',
      },
    },
    {
      icon: AlertTriangle,
      count: overCount,
      label: 'Over Budget',
      colorClass: {
        bg: 'bg-red-100 dark:bg-red-950/50',
        icon: 'text-red-600 dark:text-red-400',
        ring: 'ring-red-500/20',
      },
    },
  ];

  const total = safeCount + warningCount + overCount;

  return (
    <div className={cn('space-y-3', className)}>
      {/* Visual status indicators */}
      <div className="grid grid-cols-3 gap-2 sm:gap-3">
        {statuses.map((status, index) => (
          <div
            key={index}
            className={cn(
              'flex flex-col items-center justify-center p-3 sm:p-4 rounded-xl',
              status.colorClass.bg,
              'ring-1',
              status.colorClass.ring
            )}
          >
            <status.icon
              className={cn('w-5 h-5 sm:w-6 sm:h-6 mb-1.5 sm:mb-2', status.colorClass.icon)}
            />
            <span className="text-xl sm:text-2xl font-bold">{status.count}</span>
            <span className="text-[10px] sm:text-xs text-muted-foreground whitespace-nowrap">
              {status.label}
            </span>
          </div>
        ))}
      </div>

      {/* Summary message */}
      {total > 0 && (
        <p className="text-xs text-center text-muted-foreground">
          {total} budget{total !== 1 ? 's' : ''} tracked for {monthLabel}
        </p>
      )}
    </div>
  );
}
