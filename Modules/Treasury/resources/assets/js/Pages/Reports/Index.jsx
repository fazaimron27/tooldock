/**
 * Treasury Reports - Multiple Report Types
 * Features: Transaction, Budget, Category, Wallet, Goal, and Summary reports
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { cn } from '@/Utils/utils';
import SearchableSelect, {
  categoriesToOptions,
  walletsToOptions,
} from '@Treasury/Components/SearchableSelect';
import { router } from '@inertiajs/react';
// eslint-disable-next-line no-unused-vars
import { AnimatePresence, motion } from 'framer-motion';
import {
  BarChart3,
  Download,
  Filter,
  History,
  PieChart,
  Target,
  TrendingUp,
  Wallet,
  X,
} from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

import DatePicker from '@/Components/Form/DatePicker';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';

// Report Components
import BudgetReport from './Components/BudgetReport';
import CategoryReport from './Components/CategoryReport';
import GoalReport from './Components/GoalReport';
import SummaryReport from './Components/SummaryReport';
import TransactionReport from './Components/TransactionReport';
import WalletReport from './Components/WalletReport';

const REPORT_TYPES = [
  { value: 'transaction', label: 'Transactions', icon: History },
  { value: 'budget', label: 'Budget', icon: PieChart },
  { value: 'category', label: 'Categories', icon: BarChart3 },
  { value: 'wallet', label: 'Wallets', icon: Wallet },
  { value: 'goal', label: 'Goals', icon: Target },
  { value: 'summary', label: 'Summary', icon: TrendingUp },
];

export default function Index({
  reportType = 'transaction',
  transactions,
  summary,
  budgets,
  budgetTotals,
  categoryBreakdown,
  categoryTotals,
  walletData,
  walletTotals,
  goals,
  goalTotals,
  summaryData,
  summaryTotals,
  groupBy,
  wallets,
  categories,
  types,
  filters,
  referenceCurrency,
}) {
  const { formatCurrency, formatDate } = useAppearance();
  const [showFilters, setShowFilters] = useState(false);
  const [localFilters, setLocalFilters] = useState({
    start_date: filters.start_date || '',
    end_date: filters.end_date || '',
    wallet_id: filters.wallet_id || '',
    type: filters.type || '',
    category_id: filters.category_id || '',
    period: filters.period || 'this_month',
  });

  const handleReportTypeChange = (newType) => {
    router.get(
      route('treasury.reports'),
      {
        report_type: newType,
        start_date: localFilters.start_date,
        end_date: localFilters.end_date,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const applyFilters = useCallback(
    (updates) => {
      setLocalFilters((prev) => {
        const newFilters = { ...prev, ...updates };

        const params = { ...newFilters, report_type: reportType };
        Object.keys(params).forEach((k) => {
          if (params[k] === '' || params[k] === null || params[k] === undefined) {
            delete params[k];
          }
        });

        router.get(route('treasury.reports'), params, {
          preserveState: true,
          preserveScroll: true,
        });

        return newFilters;
      });
    },
    [reportType]
  );

  const handleFilterChange = useCallback(
    (key, value) => {
      // Helper to format date as YYYY-MM-DD in local timezone
      const formatLocalDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      };

      // When group_by changes, auto-adjust date range for proper financial analysis
      if (key === 'group_by') {
        const now = new Date();
        let updates = { [key]: value };

        if (value === 'year') {
          // Yearly view: Show full current year for year-to-date + projections
          const yearStart = new Date(now.getFullYear(), 0, 1);
          const yearEnd = new Date(now.getFullYear(), 11, 31);
          updates.start_date = formatLocalDate(yearStart);
          updates.end_date = formatLocalDate(yearEnd);
        } else if (value === 'month') {
          // Monthly view: Show year-to-date for trend analysis across months
          const yearStart = new Date(now.getFullYear(), 0, 1);
          const currentMonthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0);
          updates.start_date = formatLocalDate(yearStart);
          updates.end_date = formatLocalDate(currentMonthEnd);
        }

        applyFilters(updates);
      } else {
        applyFilters({ [key]: value });
      }
    },
    [applyFilters]
  );

  const handlePeriodChange = useCallback(
    (period) => {
      // Helper to format date as YYYY-MM-DD in local timezone
      const formatLocalDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      };

      const now = new Date();
      let startDate, endDate;

      switch (period) {
        case 'this_month': {
          startDate = new Date(now.getFullYear(), now.getMonth(), 1);
          endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
          break;
        }
        case 'last_3_months': {
          startDate = new Date(now.getFullYear(), now.getMonth() - 2, 1);
          endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
          break;
        }
        case 'last_6_months': {
          startDate = new Date(now.getFullYear(), now.getMonth() - 5, 1);
          endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
          break;
        }
        case 'last_12_months': {
          startDate = new Date(now.getFullYear() - 1, now.getMonth() + 1, 1);
          endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0);
          break;
        }
        case 'this_year': {
          startDate = new Date(now.getFullYear(), 0, 1);
          endDate = new Date(now.getFullYear(), 11, 31);
          break;
        }
        case 'custom':
        default:
          // Don't change dates for custom
          applyFilters({ period });
          return;
      }

      applyFilters({
        period,
        start_date: formatLocalDate(startDate),
        end_date: formatLocalDate(endDate),
      });
    },
    [applyFilters]
  );

  const handleDateChange = useCallback(
    (key, value) => {
      const dateStr = value
        ? typeof value === 'string'
          ? value
          : value.toISOString().split('T')[0]
        : '';

      // Validate date range - ensure start_date <= end_date
      const updates = { [key]: dateStr, period: 'custom' };

      if (key === 'start_date' && dateStr && localFilters.end_date) {
        if (dateStr > localFilters.end_date) {
          // If new start date is after end date, also update end date
          updates.end_date = dateStr;
        }
      } else if (key === 'end_date' && dateStr && localFilters.start_date) {
        if (dateStr < localFilters.start_date) {
          // If new end date is before start date, also update start date
          updates.start_date = dateStr;
        }
      }

      applyFilters(updates);
    },
    [applyFilters, localFilters.start_date, localFilters.end_date]
  );

  const clearFilters = useCallback(() => {
    setLocalFilters({
      start_date: '',
      end_date: '',
      wallet_id: '',
      type: '',
      category_id: '',
      period: 'this_month',
    });
    router.get(
      route('treasury.reports'),
      { report_type: reportType },
      { preserveState: true, preserveScroll: true }
    );
  }, [reportType]);

  const hasActiveFilters = useMemo(() => {
    return localFilters.wallet_id || localFilters.type || localFilters.category_id;
  }, [localFilters]);

  const walletOptions = useMemo(() => walletsToOptions(wallets || []), [wallets]);
  const categoryOptions = useMemo(() => categoriesToOptions(categories || []), [categories]);

  const handleExport = () => {
    const params = new URLSearchParams();
    Object.entries(localFilters).forEach(([key, value]) => {
      if (value) params.append(key, value);
    });
    window.location.href =
      route('treasury.reports.export') + (params.toString() ? `?${params.toString()}` : '');
  };

  return (
    <PageShell
      title="Financial Reports"
      description="Analyze your financial data with detailed reports"
      actions={
        <div className="flex items-center gap-2">
          {reportType === 'transaction' && (
            <>
              <Button
                variant="outline"
                onClick={() => setShowFilters(!showFilters)}
                className={cn(hasActiveFilters && 'border-primary')}
              >
                <Filter className="w-4 h-4 mr-2" />
                Filters
                {hasActiveFilters && <span className="ml-2 h-2 w-2 rounded-full bg-primary" />}
              </Button>
              <Button onClick={handleExport} disabled={!transactions || transactions.length === 0}>
                <Download className="w-4 h-4 mr-2" />
                Export CSV
              </Button>
            </>
          )}
        </div>
      }
    >
      {/* Report Type Tabs */}
      <Tabs value={reportType} onValueChange={handleReportTypeChange} className="mb-6">
        <TabsList className="grid w-full grid-cols-6">
          {REPORT_TYPES.map((type) => {
            const Icon = type.icon;
            return (
              <TabsTrigger key={type.value} value={type.value} className="flex items-center gap-2">
                <Icon className="w-4 h-4" />
                <span className="hidden sm:inline">{type.label}</span>
              </TabsTrigger>
            );
          })}
        </TabsList>
      </Tabs>

      {/* Date Range - Always Visible */}
      <div className="mb-6 p-4 rounded-lg border bg-card">
        <div className="flex flex-wrap items-end gap-4">
          <div className="space-y-2">
            <Label>Period</Label>
            <Select
              value={localFilters.period || 'this_month'}
              onValueChange={(v) => handlePeriodChange(v)}
            >
              <SelectTrigger className="w-[160px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="this_month">This Month</SelectItem>
                <SelectItem value="last_3_months">Last 3 Months</SelectItem>
                <SelectItem value="last_6_months">Last 6 Months</SelectItem>
                <SelectItem value="last_12_months">Last 12 Months</SelectItem>
                <SelectItem value="this_year">This Year</SelectItem>
                {localFilters.period === 'custom' && <SelectItem value="custom">Custom</SelectItem>}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Start Date</Label>
            <DatePicker
              value={localFilters.start_date}
              onChange={(date) => handleDateChange('start_date', date)}
              placeholder="Select start date"
            />
          </div>
          <div className="space-y-2">
            <Label>End Date</Label>
            <DatePicker
              value={localFilters.end_date}
              onChange={(date) => handleDateChange('end_date', date)}
              placeholder="Select end date"
            />
          </div>
          {reportType === 'summary' && (
            <div className="space-y-2">
              <Label>Group By</Label>
              <Select
                value={groupBy || 'month'}
                onValueChange={(v) => handleFilterChange('group_by', v)}
              >
                <SelectTrigger className="w-[140px]">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="month">Monthly</SelectItem>
                  <SelectItem value="year">Yearly</SelectItem>
                </SelectContent>
              </Select>
            </div>
          )}
        </div>
      </div>

      {/* Filter Panel - Transaction Report Only */}
      <AnimatePresence>
        {showFilters && reportType === 'transaction' && (
          <motion.div
            className="mb-6 rounded-lg border bg-card p-4 overflow-hidden"
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ duration: 0.2, ease: [0.4, 0, 0.2, 1] }}
          >
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-lg font-semibold">Filters</h3>
              {hasActiveFilters && (
                <Button variant="ghost" size="sm" onClick={clearFilters}>
                  <X className="mr-2 h-4 w-4" />
                  Clear Filters
                </Button>
              )}
            </div>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="space-y-2">
                <Label>Wallet</Label>
                <SearchableSelect
                  value={localFilters.wallet_id}
                  onValueChange={(v) => handleFilterChange('wallet_id', v)}
                  options={[{ value: '', label: 'All Wallets' }, ...walletOptions]}
                  placeholder="All Wallets"
                  searchPlaceholder="Search wallets..."
                />
              </div>
              <div className="space-y-2">
                <Label>Type</Label>
                <Select
                  value={localFilters.type || 'all'}
                  onValueChange={(v) => handleFilterChange('type', v === 'all' ? '' : v)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="All Types" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All Types</SelectItem>
                    {types.map((t) => (
                      <SelectItem key={t} value={t}>
                        {t.charAt(0).toUpperCase() + t.slice(1)}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Category</Label>
                <SearchableSelect
                  value={localFilters.category_id}
                  onValueChange={(v) => handleFilterChange('category_id', v)}
                  options={[{ value: '', label: 'All Categories' }, ...categoryOptions]}
                  placeholder="All Categories"
                  searchPlaceholder="Search categories..."
                  showColors
                  hierarchical
                />
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Report Content */}
      {reportType === 'transaction' && (
        <TransactionReport
          transactions={transactions}
          summary={summary}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
          referenceCurrency={referenceCurrency}
        />
      )}
      {reportType === 'budget' && (
        <BudgetReport
          budgets={budgets}
          totals={budgetTotals}
          formatCurrency={formatCurrency}
          referenceCurrency={referenceCurrency}
        />
      )}
      {reportType === 'category' && (
        <CategoryReport
          data={categoryBreakdown}
          totals={categoryTotals}
          formatCurrency={formatCurrency}
          referenceCurrency={referenceCurrency}
        />
      )}
      {reportType === 'wallet' && (
        <WalletReport
          data={walletData}
          totals={walletTotals}
          formatCurrency={formatCurrency}
          referenceCurrency={referenceCurrency}
        />
      )}
      {reportType === 'goal' && (
        <GoalReport
          goals={goals}
          totals={goalTotals}
          formatCurrency={formatCurrency}
          formatDate={formatDate}
          referenceCurrency={referenceCurrency}
        />
      )}
      {reportType === 'summary' && (
        <SummaryReport
          data={summaryData}
          totals={summaryTotals}
          formatCurrency={formatCurrency}
          referenceCurrency={referenceCurrency}
        />
      )}
    </PageShell>
  );
}
