import { cn } from '@/Utils/utils';

/**
 * Reusable progress bar component
 * @param {object} props
 * @param {string} props.label - Label text (e.g., "CPU Usage")
 * @param {string|number} props.value - Value to display (e.g., "45%" or 45)
 * @param {number} props.percentage - Percentage value (0-100) for the progress bar
 * @param {string} props.className - Additional CSS classes for the container
 * @param {string} props.barClassName - Additional CSS classes for the progress bar
 * @param {string} props.color - Progress bar color variant (default: "primary")
 */
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

  return (
    <div className={cn('space-y-1', className)}>
      <div className="flex justify-between text-sm">
        <span>{label}</span>
        <span>{displayValue}</span>
      </div>
      <div className="h-2 bg-secondary rounded-full overflow-hidden">
        <div
          className={cn(
            'h-full transition-all duration-300',
            colorClasses[color] || colorClasses.primary,
            barClassName
          )}
          style={{ width: `${clampedPercentage}%` }}
        />
      </div>
    </div>
  );
}
