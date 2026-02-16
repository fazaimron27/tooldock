/**
 * Currency Input with localized thousand separators
 * Uses Intl.NumberFormat to dynamically get currency formatting info
 */
import { getCurrencyInfo } from '@/Utils/format';
import { cn } from '@/Utils/utils';
import { usePage } from '@inertiajs/react';
import { forwardRef, useCallback, useMemo } from 'react';

import { Input } from '@/Components/ui/input';

/**
 * Format a number string with thousand separators based on locale
 */
function formatWithSeparator(value, thousandSep, decimalSep, decimals) {
  if (!value) return '';

  let strValue = String(value);

  let integerPart = strValue;
  let decimalPart = '';
  if (strValue.includes('.')) {
    const parts = strValue.split('.');
    integerPart = parts[0];
    decimalPart = parts[1] || '';
  }

  const cleanInt = integerPart.replace(/[^\d]/g, '');
  if (!cleanInt) return '';

  const formatted = cleanInt.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
  if (decimals > 0 && decimalPart) {
    return `${formatted}${decimalSep}${decimalPart.slice(0, decimals)}`;
  }

  return formatted;
}

/**
 * Remove thousand separators to get clean number
 */
function cleanNumber(value, thousandSep, decimalSep) {
  if (!value) return '';
  const escapedSep = thousandSep.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const sepRegex = new RegExp(escapedSep, 'g');
  let cleaned = value.replace(sepRegex, '');
  if (decimalSep !== '.') {
    cleaned = cleaned.replace(decimalSep, '.');
  }
  return cleaned;
}

const CurrencyInput = forwardRef(function CurrencyInput(
  {
    value,
    onChange,
    showSymbol = true,
    className,
    currencyCode: propCurrencyCode,
    currencySymbol: propCurrencySymbol,
    ...props
  },
  ref
) {
  const { currency_code, currency_symbol } = usePage().props;
  const currencyCode = propCurrencyCode || currency_code || 'IDR';

  const { symbol, decimals, thousandSep, decimalSep } = useMemo(() => {
    const info = getCurrencyInfo(currencyCode);
    return {
      symbol: propCurrencySymbol || currency_symbol || info.symbol,
      decimals: info.decimals,
      thousandSep: info.thousandSep,
      decimalSep: info.decimalSep,
    };
  }, [currencyCode, propCurrencySymbol, currency_symbol]);

  const displayValue = useMemo(() => {
    return formatWithSeparator(String(value || ''), thousandSep, decimalSep, decimals);
  }, [value, thousandSep, decimalSep, decimals]);

  const handleChange = useCallback(
    (e) => {
      const inputValue = e.target.value;
      const cleanedValue = cleanNumber(inputValue, thousandSep, decimalSep);
      onChange?.(cleanedValue);
    },
    [onChange, thousandSep, decimalSep]
  );

  if (showSymbol) {
    return (
      <div className="relative">
        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground z-10">
          {symbol}
        </span>
        <Input
          ref={ref}
          type="text"
          inputMode={decimals === 0 ? 'numeric' : 'decimal'}
          value={displayValue}
          onChange={handleChange}
          className={cn('pl-10 tabular-nums', className)}
          {...props}
        />
      </div>
    );
  }

  return (
    <Input
      ref={ref}
      type="text"
      inputMode={decimals === 0 ? 'numeric' : 'decimal'}
      value={displayValue}
      onChange={handleChange}
      className={cn('tabular-nums', className)}
      {...props}
    />
  );
});

export default CurrencyInput;
