/**
 * Wallet list item component for displaying wallet info
 * Shows wallet icon, name, type, currency badge, and balance
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { cn } from '@/Utils/utils';
import { getWalletColor, getWalletIcon } from '@Treasury/Utils/walletIcons';

import { Badge } from '@/Components/ui/badge';

export default function WalletListItem({ wallet, onClick, className }) {
  const { formatCurrency } = useAppearance();
  const WalletIcon = getWalletIcon(wallet.type);
  const walletColor = getWalletColor(wallet.type);

  const content = (
    <div
      className={cn(
        'flex items-center justify-between p-3 rounded-lg bg-muted/50',
        onClick && 'cursor-pointer hover:bg-muted transition-colors',
        className
      )}
      onClick={onClick}
    >
      <div className="flex items-center gap-3">
        <div
          className="w-10 h-10 rounded-xl flex items-center justify-center"
          style={{ backgroundColor: `${walletColor}15` }}
        >
          <WalletIcon className="w-5 h-5" style={{ color: walletColor }} />
        </div>
        <div>
          <p className="font-medium">{wallet.name}</p>
          <div className="flex items-center gap-2">
            <p className="text-xs text-muted-foreground capitalize">{wallet.type}</p>
            {wallet.currency && (
              <Badge variant="outline" className="text-[10px] px-1.5 py-0 h-4 font-medium">
                {wallet.currency}
              </Badge>
            )}
          </div>
        </div>
      </div>
      <p className="font-bold">{formatCurrency(wallet.balance, wallet.currency)}</p>
    </div>
  );

  return content;
}
