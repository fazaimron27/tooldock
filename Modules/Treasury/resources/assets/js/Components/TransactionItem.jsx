/**
 * TransactionItem - Reusable transaction display component
 * Supports different variants: default, compact, detailed
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { cn } from '@/Utils/utils';
import { getCategoryIcon } from '@Treasury/Utils/categoryIcons';
import { getGoalIcon } from '@Treasury/Utils/goalIcons';
import { RefreshCw, Target, TrendingDown, TrendingUp } from 'lucide-react';

// Re-export getCategoryIcon for backward compatibility
export { getCategoryIcon };

// Transaction type configuration
export const typeConfig = {
  income: {
    icon: TrendingUp,
    iconClass: 'text-emerald-500',
    bgClass: 'bg-emerald-500/10',
    amountClass: 'text-emerald-600 dark:text-emerald-400',
    prefix: '+',
  },
  expense: {
    icon: TrendingDown,
    iconClass: 'text-rose-500',
    bgClass: 'bg-rose-500/10',
    amountClass: 'text-rose-600 dark:text-rose-400',
    prefix: '-',
  },
  transfer: {
    icon: RefreshCw,
    iconClass: 'text-blue-500',
    bgClass: 'bg-blue-500/10',
    amountClass: 'text-blue-600 dark:text-blue-400',
    prefix: '',
  },
};

// Format date for display with relative formats
function formatTransactionDate(dateString) {
  if (!dateString) return '';

  const date = new Date(dateString);
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);

  const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
  const yesterdayOnly = new Date(
    yesterday.getFullYear(),
    yesterday.getMonth(),
    yesterday.getDate()
  );

  if (dateOnly.getTime() === todayOnly.getTime()) {
    return 'Today';
  } else if (dateOnly.getTime() === yesterdayOnly.getTime()) {
    return 'Yesterday';
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
    });
  }
}

export default function TransactionItem({ transaction, onClick, className, variant = 'default' }) {
  const { formatCurrency } = useAppearance();

  // Handle incoming transfers specially
  const isTransfer = transaction.type === 'transfer';
  const isIncomingTransfer = transaction.is_incoming_transfer;
  const isGoalAllocation = !!transaction.goal;

  // For incoming transfers, use income-like styling; for outgoing transfers use transfer styling
  const effectiveConfig = isIncomingTransfer
    ? { ...typeConfig.income, prefix: '+' }
    : typeConfig[transaction.type] || typeConfig.expense;

  // Determine icon and color based on transaction type
  let CategoryIcon;
  let categoryColor;

  if (isGoalAllocation) {
    // For goal allocations, use the goal's category icon
    CategoryIcon = getGoalIcon(transaction.goal?.category?.slug);
    categoryColor = transaction.goal?.category?.color || '#8b5cf6'; // Purple fallback for goals
  } else if (isTransfer) {
    // For transfers, use the transfer icon
    CategoryIcon = typeConfig.transfer.icon;
    categoryColor = isIncomingTransfer ? '#22c55e' : '#3b82f6';
  } else {
    // For regular transactions, use category icon
    CategoryIcon = getCategoryIcon(transaction.category?.slug);
    categoryColor = transaction.category?.color || '#6b7280';
  }

  const displayDate = formatTransactionDate(transaction.date);

  // For transfers, show appropriate name based on direction
  const displayName = isTransfer
    ? transaction.name || (isIncomingTransfer ? 'Transfer In' : 'Transfer Out')
    : transaction.name || transaction.description || transaction.category?.name || 'Transaction';

  // For transfers, show wallet flow
  const walletName =
    isTransfer && transaction.destination_wallet
      ? `${transaction.wallet?.name || 'Wallet'} → ${transaction.destination_wallet?.name || 'Wallet'}`
      : transaction.wallet?.name || transaction.wallet;

  // Check if has attachments
  const hasAttachments = transaction.attachments && transaction.attachments.length > 0;

  if (variant === 'compact') {
    return (
      <div
        className={cn(
          'flex items-center justify-between py-2.5 group',
          onClick && 'cursor-pointer',
          className
        )}
        onClick={onClick}
      >
        <div className="flex items-center gap-3 min-w-0 flex-1">
          <div
            className="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
            style={{ backgroundColor: `${categoryColor}15` }}
          >
            <CategoryIcon className="w-4 h-4" style={{ color: categoryColor }} />
          </div>
          <div className="min-w-0 flex-1">
            <p className="font-medium text-sm text-foreground truncate leading-tight">
              {displayName}
            </p>
            <p className="text-xs text-muted-foreground truncate">{walletName}</p>
          </div>
        </div>
        <div className="text-right shrink-0 pl-3">
          <p className={cn('font-semibold tabular-nums text-sm', effectiveConfig.amountClass)}>
            {effectiveConfig.prefix}
            {formatCurrency(transaction.amount, transaction.wallet?.currency)}
          </p>
          {parseFloat(transaction.fee || 0) > 0 && (
            <p className="text-[10px] text-muted-foreground">
              +Fee: {formatCurrency(transaction.fee, transaction.wallet?.currency)}
            </p>
          )}
          <p className="text-[11px] text-muted-foreground">{displayDate}</p>
        </div>
      </div>
    );
  }

  // Default variant
  return (
    <div
      className={cn(
        'flex items-center gap-4 py-3 px-1 group transition-colors',
        onClick && 'cursor-pointer hover:bg-muted/50 rounded-lg',
        className
      )}
      onClick={onClick}
    >
      {/* Icon */}
      <div className="relative">
        <div
          className="w-11 h-11 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105"
          style={{
            backgroundColor: `${categoryColor}15`,
            boxShadow: `0 0 0 1px ${categoryColor}15`,
          }}
        >
          <CategoryIcon className="w-5 h-5" style={{ color: categoryColor }} />
        </div>
        {/* Type indicator - show Target for goal allocations */}
        <div
          className={cn(
            'absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full flex items-center justify-center border-2 border-background',
            isGoalAllocation ? 'bg-violet-500/20' : effectiveConfig.bgClass
          )}
        >
          {isGoalAllocation ? (
            <Target className="w-2.5 h-2.5 text-violet-500" />
          ) : (
            <effectiveConfig.icon className={cn('w-2.5 h-2.5', effectiveConfig.iconClass)} />
          )}
        </div>
      </div>

      {/* Details */}
      <div className="flex-1 min-w-0 py-0.5">
        <div className="flex items-center gap-2 justify-between">
          <p className="font-semibold text-sm md:text-[15px] text-foreground truncate leading-tight flex-1">
            {displayName}
          </p>
          {hasAttachments && <span className="text-muted-foreground text-xs shrink-0">📎</span>}
        </div>

        <div className="flex items-center flex-wrap gap-x-1.5 gap-y-0.5 mt-1 text-muted-foreground">
          <span className="text-[10px] md:text-xs truncate max-w-[80px] md:max-w-none">
            {walletName}
          </span>
          <span className="text-muted-foreground/40 text-[10px]">•</span>
          <span className="text-[10px] md:text-xs shrink-0">{displayDate}</span>

          {/* Show goal badge for goal allocations - carefully truncated on mobile */}
          {isGoalAllocation && (
            <div className="flex items-center gap-1.5 shrink-0">
              <span className="text-muted-foreground/40 text-[10px] hidden sm:inline">•</span>
              <span
                className="hidden sm:inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded-full font-medium truncate max-w-[100px]"
                style={{
                  backgroundColor: `${categoryColor}15`,
                  color: categoryColor,
                }}
              >
                <Target className="w-2.5 h-2.5" />
                {transaction.goal.name}
              </span>
            </div>
          )}

          {/* Show category badge for regular transactions - hide on smallest mobile */}
          {!isGoalAllocation && transaction.category && (
            <div className="flex items-center gap-1.5 shrink-0">
              <span className="text-muted-foreground/40 text-[10px] hidden sm:inline">•</span>
              <span
                className="hidden sm:inline-flex text-[10px] px-1.5 py-0.5 rounded-full font-medium truncate max-w-[80px]"
                style={{
                  backgroundColor: `${categoryColor}15`,
                  color: categoryColor,
                }}
              >
                {transaction.category.name}
              </span>
            </div>
          )}
        </div>
      </div>

      {/* Amount */}
      <div className="text-right shrink-0 pl-2">
        {/* For incoming cross-currency transfers, show the converted amount in destination currency */}
        {isIncomingTransfer && transaction.converted_amount ? (
          <p
            className={cn(
              'font-bold text-sm md:text-base tabular-nums whitespace-nowrap',
              effectiveConfig.amountClass
            )}
          >
            {effectiveConfig.prefix}
            {formatCurrency(transaction.converted_amount, transaction.destination_wallet?.currency)}
            <span className="text-[10px] md:text-xs text-muted-foreground font-normal ml-1">
              ({formatCurrency(transaction.amount, transaction.wallet?.currency)})
            </span>
          </p>
        ) : (
          <p
            className={cn(
              'font-bold text-sm md:text-base tabular-nums whitespace-nowrap',
              effectiveConfig.amountClass
            )}
          >
            {effectiveConfig.prefix}
            {formatCurrency(transaction.amount, transaction.wallet?.currency)}
          </p>
        )}
        {parseFloat(transaction.fee || 0) > 0 && (
          <p className="text-[10px] md:text-xs text-muted-foreground">
            Fee: {formatCurrency(transaction.fee, transaction.wallet?.currency)}
          </p>
        )}
      </div>
    </div>
  );
}
