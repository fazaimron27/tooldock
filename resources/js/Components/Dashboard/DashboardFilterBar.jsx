import { cn } from '@/Utils/utils';
import { router } from '@inertiajs/react';
import { Filter, X } from 'lucide-react';
import { useCallback, useState } from 'react';

import DatePicker from '@/Components/Form/DatePicker';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

export default function DashboardFilterBar({ filters = {}, availableWallets = [], className }) {
  const [localFilters, setLocalFilters] = useState({
    wallet_id: filters.wallet_id || 'all',
    date_from: filters.date_from || '',
    date_to: filters.date_to || '',
  });

  const applyFilters = useCallback((newFilters) => {
    const query = { ...newFilters };
    if (query.wallet_id === 'all') delete query.wallet_id;
    if (!query.date_from) delete query.date_from;
    if (!query.date_to) delete query.date_to;

    router.get(window.location.pathname, query, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }, []);

  const handleWalletChange = (value) => {
    const updated = { ...localFilters, wallet_id: value };
    setLocalFilters(updated);
    applyFilters(updated);
  };

  const handleDateChange = (type, value) => {
    const updated = { ...localFilters, [type]: value };
    setLocalFilters(updated);
    applyFilters(updated);
  };

  const resetFilters = () => {
    const reset = { wallet_id: 'all', date_from: '', date_to: '' };
    setLocalFilters(reset);
    applyFilters(reset);
  };

  const hasActiveFilters =
    localFilters.wallet_id !== 'all' || localFilters.date_from || localFilters.date_to;

  return (
    <Card className={cn('mb-6 border-none shadow-sm bg-muted/30', className)}>
      <CardContent className="p-4">
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground mr-2">
            <Filter className="h-4 w-4" />
            <span>Filters:</span>
          </div>

          {/* Wallet Filter */}
          <div className="w-full sm:w-48">
            <Select value={localFilters.wallet_id} onValueChange={handleWalletChange}>
              <SelectTrigger className="bg-background">
                <SelectValue placeholder="All Wallets" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Wallets</SelectItem>
                {availableWallets.map((wallet) => (
                  <SelectItem key={wallet.id} value={wallet.id}>
                    {wallet.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Date Range */}
          <div className="flex flex-wrap items-center gap-2">
            <span className="text-xs text-muted-foreground ml-2">Range:</span>
            <DatePicker
              value={localFilters.date_from}
              onChange={(val) => handleDateChange('date_from', val)}
              placeholder="From"
              className="w-full sm:w-40"
            />
            <span className="text-xs text-muted-foreground">—</span>
            <DatePicker
              value={localFilters.date_to}
              onChange={(val) => handleDateChange('date_to', val)}
              placeholder="To"
              className="w-full sm:w-40"
            />
          </div>

          {/* Reset Button */}
          {hasActiveFilters && (
            <Button
              variant="ghost"
              size="sm"
              onClick={resetFilters}
              className="ml-auto text-xs h-9 px-2 text-muted-foreground hover:text-foreground"
            >
              <X className="h-4 w-4 mr-1" />
              Reset Filters
            </Button>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
