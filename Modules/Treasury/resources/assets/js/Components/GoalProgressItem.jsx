/**
 * Goal progress item component
 * Shows goal icon, name, progress bar, and amount progress
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { getGoalIcon } from '@Treasury/Utils/goalIcons';
import { Link } from '@inertiajs/react';
import { CheckCircle } from 'lucide-react';

export default function GoalProgressItem({ goal, showLink = true }) {
  const { formatCurrency } = useAppearance();
  const progress = goal.progress ?? goal.progress_percentage ?? 0;

  const IconComponent = getGoalIcon(goal.category?.slug);
  const categoryColor = goal.category?.color || '#6B7280';

  const content = (
    <div className="flex items-start gap-4 p-2 -mx-2 rounded-xl hover:bg-muted/50 transition-all duration-200 cursor-pointer">
      {/* Icon - Sized and styled to match TransactionItem (w-11 h-11) */}
      <div
        className="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 mt-0.5 transition-transform"
        style={{
          backgroundColor: `${categoryColor}15`,
          boxShadow: `0 0 0 1px ${categoryColor}15`,
        }}
      >
        {goal.is_completed ? (
          <CheckCircle className="w-5 h-5" style={{ color: categoryColor }} />
        ) : (
          <IconComponent className="w-5 h-5" style={{ color: categoryColor }} />
        )}
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <div className="flex items-center justify-between mb-0.5">
          <span className="font-semibold text-[15px] text-foreground truncate leading-tight">
            {goal.name}
          </span>
          <span className="text-sm font-bold ml-2 shrink-0">{progress}%</span>
        </div>
        <p className="text-xs text-muted-foreground mb-2">
          {formatCurrency(goal.saved_amount ?? 0, goal.currency)} /{' '}
          {formatCurrency(goal.target_amount, goal.currency)}
        </p>

        {/* Progress bar */}
        <div className="h-1.5 bg-muted rounded-full overflow-hidden">
          <div
            className="h-full rounded-full transition-all duration-500 ease-out"
            style={{
              width: `${Math.min(progress, 100)}%`,
              backgroundColor: categoryColor,
            }}
          />
        </div>
      </div>
    </div>
  );

  if (showLink) {
    return <Link href={route('treasury.goals.show', goal.id)}>{content}</Link>;
  }

  return content;
}
