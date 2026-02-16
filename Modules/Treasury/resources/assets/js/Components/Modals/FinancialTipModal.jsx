/**
 * Financial Tip Modal Component
 *
 * Displays smart financial guidance and target amount recommendations
 * for Emergency & Security goal categories based on user's transaction history.
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { getLocalDateString } from '@/Utils/format';
import { AlertCircle, Calendar, Lightbulb, TrendingDown, TrendingUp } from 'lucide-react';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

/**
 * Map category types to display names and icons
 */
const CATEGORY_CONFIG = {
  emergency_fund: {
    title: 'Emergency Fund',
    icon: AlertCircle,
    dataLabel: 'Average Monthly Expenses',
    dataIcon: TrendingDown,
  },
  job_loss_fund: {
    title: 'Job Loss Fund',
    icon: AlertCircle,
    dataLabel: 'Average Monthly Expenses',
    dataIcon: TrendingDown,
  },
  insurance_fund: {
    title: 'Insurance Fund',
    icon: AlertCircle,
    dataLabel: 'Average Monthly Income',
    dataIcon: TrendingUp,
  },
};

/**
 * Calculate target date based on months to build from today
 */
function calculateTargetDate(monthsToBuild) {
  const date = new Date();
  date.setMonth(date.getMonth() + monthsToBuild);
  return getLocalDateString(date);
}

/**
 * Format months to human readable duration
 */
function formatDuration(months) {
  if (months === 1) return '1 month';
  if (months < 12) return `${months} months`;
  const years = Math.floor(months / 12);
  const remainingMonths = months % 12;
  if (remainingMonths === 0) {
    return years === 1 ? '1 year' : `${years} years`;
  }
  return `${years} year${years > 1 ? 's' : ''} ${remainingMonths} month${remainingMonths > 1 ? 's' : ''}`;
}

export default function FinancialTipModal({ open, onOpenChange, data, onApply }) {
  const { formatCurrency } = useAppearance();

  if (!data) {
    return null;
  }

  const config = CATEGORY_CONFIG[data.category_type];
  if (!config) {
    return null;
  }

  const DataIcon = config.dataIcon;
  const hasSufficientData = data.has_sufficient_data !== false;

  // Check if currency was converted (wallet currency differs from reference)
  const isConverted = data.reference_currency && data.reference_currency !== data.currency;

  // Display amounts - always show reference currency as primary (calculation basis)
  const referenceAvg =
    data.category_type === 'insurance_fund'
      ? (data.reference_avg_income ?? data.avg_income)
      : (data.reference_avg_expense ?? data.avg_expense);
  const referenceCurrency = data.reference_currency ?? data.currency;

  // Converted amounts for wallet currency
  const walletAvg = data.avg_income;
  const walletCurrency = data.currency;

  const handleApply = (suggestion) => {
    const targetDate = suggestion.months_to_build
      ? calculateTargetDate(suggestion.months_to_build)
      : null;
    // Apply the wallet currency amount (converted if applicable)
    onApply?.({
      amount: suggestion.amount,
      targetDate,
    });
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader className="px-6 pt-6 pb-2">
          <DialogTitle className="flex items-center gap-2 text-lg">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
              <Lightbulb className="h-4 w-4 text-amber-600 dark:text-amber-400" />
            </span>
            Financial Tip: {config.title}
          </DialogTitle>
          <DialogDescription>
            {hasSufficientData
              ? `Based on your last ${data.months_analyzed} months of transactions`
              : 'Based on your last months of transactions'}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 px-6 py-2">
          {/* Average Data Display - show in reference currency */}
          <div className="rounded-lg border bg-muted/50 p-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                <DataIcon className="h-5 w-5 text-primary" />
              </div>
              <div>
                <p className="text-sm text-muted-foreground">{config.dataLabel}</p>
                {hasSufficientData ? (
                  <>
                    <p className="text-xl font-bold font-mono">
                      {formatCurrency(referenceAvg, referenceCurrency)}
                    </p>
                    {isConverted && (
                      <p className="text-xs text-muted-foreground">
                        Converts to {formatCurrency(walletAvg, walletCurrency)}
                      </p>
                    )}
                  </>
                ) : (
                  <p className="text-sm text-muted-foreground italic">Not enough data</p>
                )}
              </div>
            </div>
          </div>

          {/* Description */}
          <p className="text-sm text-muted-foreground leading-relaxed">{data.description}</p>

          {/* Suggestions - only show when there's sufficient data */}
          {hasSufficientData && data.suggestions?.length > 0 ? (
            <div className="space-y-2">
              <p className="text-sm font-medium">Recommended Targets:</p>
              <div className="space-y-2">
                {data.suggestions.map((suggestion, index) => {
                  // Reference amount is original, wallet amount is converted
                  const refAmount = suggestion.reference_amount ?? suggestion.amount;
                  const walletAmount = suggestion.amount;

                  return (
                    <div
                      key={index}
                      className="flex items-center justify-between rounded-lg border bg-background p-3 transition-colors hover:bg-muted/50"
                    >
                      <div className="space-y-1">
                        <p className="text-sm font-medium">{suggestion.label}</p>
                        <div>
                          <p className="text-lg font-bold font-mono text-primary">
                            {formatCurrency(refAmount, referenceCurrency)}
                          </p>
                          {isConverted && (
                            <p className="text-xs text-muted-foreground">
                              Converts to {formatCurrency(walletAmount, walletCurrency)}
                            </p>
                          )}
                        </div>
                        {suggestion.months_to_build && (
                          <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Calendar className="h-3 w-3" />
                            Target: {formatDuration(suggestion.months_to_build)}
                          </p>
                        )}
                      </div>
                      <Button variant="outline" size="sm" onClick={() => handleApply(suggestion)}>
                        Apply
                      </Button>
                    </div>
                  );
                })}
              </div>
            </div>
          ) : (
            <div className="space-y-2">
              <p className="text-sm font-medium">Recommended Targets:</p>
              <p className="text-sm text-muted-foreground italic">
                Add more transactions to receive personalized recommendations.
              </p>
            </div>
          )}

          {/* Tip */}
          <div className="flex gap-2 rounded-lg bg-blue-50 dark:bg-blue-950/30 p-3 text-sm text-blue-700 dark:text-blue-300">
            <Lightbulb className="h-4 w-4 mt-0.5 shrink-0" />
            <p>{data.tip}</p>
          </div>

          {/* Formula Reference - only show when sufficient data */}
          {hasSufficientData && data.formula && (
            <p className="text-xs text-center text-muted-foreground">
              Formula: <span className="font-mono">{data.formula}</span>
            </p>
          )}
        </div>

        <DialogFooter className="px-6 pb-6 pt-2 gap-2 sm:gap-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Dismiss
          </Button>
          {data.action_url ? (
            <Button variant="default" asChild>
              <a href={data.action_url}>{data.action_label || 'Add Transaction'}</a>
            </Button>
          ) : (
            <Button variant="default" onClick={() => onOpenChange(false)}>
              Got It
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
