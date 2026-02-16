/**
 * Net Worth Banner component
 * Hero banner showing total net worth with gradient background and decorative elements
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { Landmark, TrendingDown, TrendingUp, Wallet } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Card, CardContent } from '@/Components/ui/card';

export default function NetWorthBanner({
  total = 0,
  walletCount = 0,
  changePercent = null,
  changeAmount = null,
}) {
  const { formatCurrency, currencyCode } = useAppearance();

  const isPositive = changePercent === null ? null : changePercent >= 0;

  return (
    <Card className="relative overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-rose-500 text-white border-0 shadow-lg">
      {/* Decorative background patterns */}
      <div className="absolute inset-0 overflow-hidden">
        {/* Animated circles */}
        <div className="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-2xl" />
        <div className="absolute -bottom-16 -left-16 w-56 h-56 bg-white/5 rounded-full blur-3xl" />
        <div className="absolute top-1/2 right-1/4 w-24 h-24 bg-white/5 rounded-full blur-xl" />

        {/* Grid pattern overlay */}
        <div
          className="absolute inset-0 opacity-[0.03]"
          style={{
            backgroundImage: `linear-gradient(to right, white 1px, transparent 1px),
                              linear-gradient(to bottom, white 1px, transparent 1px)`,
            backgroundSize: '40px 40px',
          }}
        />
      </div>

      <CardContent className="relative pt-6 pb-8">
        <div className="flex items-start justify-between">
          <div className="space-y-1">
            {/* Label with currency badge */}
            <div className="flex items-center gap-2">
              <p className="text-sm font-medium text-white/80">Total Net Worth</p>
              <Badge
                variant="secondary"
                className="text-[10px] px-1.5 py-0 h-4 bg-white/20 text-white border-0 backdrop-blur-sm"
              >
                {currencyCode}
              </Badge>
            </div>

            {/* Main amount */}
            <p className="text-4xl md:text-5xl font-bold tracking-tight">{formatCurrency(total)}</p>

            {/* Change indicator and wallet count */}
            <div className="flex items-center gap-4 mt-3">
              {/* Wallet count */}
              <div className="flex items-center gap-1.5 text-white/70">
                <Wallet className="w-4 h-4" />
                <span className="text-sm">
                  Across {walletCount} wallet{walletCount !== 1 ? 's' : ''}
                </span>
              </div>

              {/* Change indicator (if data available) */}
              {changePercent !== null && (
                <div
                  className={`flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${
                    isPositive ? 'bg-green-500/20 text-green-100' : 'bg-red-500/20 text-red-100'
                  }`}
                >
                  {isPositive ? (
                    <TrendingUp className="w-3 h-3" />
                  ) : (
                    <TrendingDown className="w-3 h-3" />
                  )}
                  <span>
                    {isPositive ? '+' : ''}
                    {changePercent.toFixed(1)}%
                  </span>
                  {changeAmount !== null && (
                    <span className="text-white/60 ml-1">
                      ({isPositive ? '+' : ''}
                      {formatCurrency(changeAmount)})
                    </span>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Icon with glassmorphism effect */}
          <div className="flex items-center justify-center w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 shadow-lg">
            <Landmark className="w-10 h-10 text-white/80" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
