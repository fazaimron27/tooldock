/**
 * Progress bar component for displaying percentage-based metrics
 * Supports multiple color variants and displays both label and value
 */
import { cn } from '@/Utils/utils';

export default function ProgressBar({
  label,
  value,
  percentage,
  className,
  barClassName,
  color = 'primary',
}) {
  // Calculate percentage if value is provided as number
  const progressPercentage =
    percentage !== undefined
      ? percentage
      : typeof value === 'number'
        ? value
        : parseFloat(value) || 0;

  // Ensure percentage is between 0 and 100
  const clampedPercentage = Math.min(Math.max(progressPercentage, 0), 100);

  // Format value display
  const displayValue = typeof value === 'string' ? value : `${value}%`;

  // Color variants
  const colorClasses = {
    primary: 'bg-primary',
    destructive: 'bg-destructive',
    success: 'bg-green-500',
    warning: 'bg-yellow-500',
    info: 'bg-blue-500',
  };

  // Determine if color is a variant or dynamic hex
  const isVariant = Object.keys(colorClasses).includes(color);

  return (
    <div className={cn('space-y-1', className)}>
      <div className="flex justify-between text-sm">
        <span className="font-medium">{label}</span>
        <span className="text-muted-foreground">{displayValue}</span>
      </div>
      <div className="h-2 bg-secondary rounded-full overflow-hidden">
        <div
          className={cn(
            'h-full transition-all duration-300',
            isVariant ? colorClasses[color] : barClassName
          )}
          style={{
            width: `${clampedPercentage}%`,
            backgroundColor: !isVariant ? color : undefined,
          }}
        />
      </div>
    </div>
  );
}
