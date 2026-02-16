/**
 * Wallets Index page - List all user wallets
 * Wallet types are now fetched from Categories system
 */
import { useAppearance } from '@/Hooks/useAppearance';
import EmptyState from '@Treasury/Components/EmptyState';
import NetWorthBanner from '@Treasury/Components/NetWorthBanner';
import NetWorthChart from '@Treasury/Components/NetWorthChart';
import { getWalletColor, getWalletIcon } from '@Treasury/Utils/walletIcons';
import { Link, router } from '@inertiajs/react';
import { CreditCard, Eye, MoreVertical, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

export default function Index({ wallets, totals, walletTypes = [], netWorthHistory = [] }) {
  const { formatCurrency } = useAppearance();
  const [deleteWallet, setDeleteWallet] = useState(null);

  // Create a map of wallet type slug to type info for quick lookup
  const walletTypeMap = useMemo(() => {
    return walletTypes.reduce((map, type) => {
      map[type.slug] = type;
      return map;
    }, {});
  }, [walletTypes]);

  // Helper to get wallet type display name
  const getWalletTypeName = (typeSlug) => {
    return walletTypeMap[typeSlug]?.name || typeSlug;
  };

  // Helper to get wallet type color from Categories (fallback to utility function)
  const getWalletTypeColor = (typeSlug) => {
    return walletTypeMap[typeSlug]?.color || getWalletColor(typeSlug);
  };

  const handleDelete = () => {
    if (deleteWallet) {
      router.delete(route('treasury.wallets.destroy', deleteWallet.id), {
        onSuccess: () => setDeleteWallet(null),
      });
    }
  };

  return (
    <PageShell
      title="Wallets"
      description="Manage your cash, bank accounts, and e-wallets"
      actions={
        <Link href={route('treasury.wallets.create')}>
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Add Wallet
          </Button>
        </Link>
      }
    >
      {/* Summary Banner */}
      <div className="mb-6">
        <NetWorthBanner total={totals?.total || 0} walletCount={wallets?.length || 0} />
      </div>

      {/* Net Worth Trend Chart */}
      {netWorthHistory && netWorthHistory.length > 0 && (
        <Card className="mb-6">
          <CardHeader className="pb-2">
            <CardTitle className="text-base">Net Worth Trend (Last 6 Months)</CardTitle>
          </CardHeader>
          <CardContent>
            <NetWorthChart data={netWorthHistory} />
          </CardContent>
        </Card>
      )}

      {/* Wallet Grid */}
      {wallets && wallets.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {wallets.map((wallet) => {
            const WalletIcon = getWalletIcon(wallet.type);
            const walletColor = getWalletTypeColor(wallet.type);
            return (
              <Card key={wallet.id} className="hover:shadow-md transition-shadow">
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                  <div className="flex items-center gap-3">
                    <div
                      className="w-10 h-10 rounded-xl flex items-center justify-center"
                      style={{ backgroundColor: `${walletColor}15` }}
                    >
                      <WalletIcon className="w-5 h-5" style={{ color: walletColor }} />
                    </div>
                    <div>
                      <h3 className="font-semibold">{wallet.name}</h3>
                      <div className="flex items-center gap-2">
                        <p className="text-xs text-muted-foreground">
                          {getWalletTypeName(wallet.type)}
                        </p>
                        {wallet.currency && (
                          <Badge
                            variant="outline"
                            className="text-[10px] px-1.5 py-0 h-4 font-medium"
                          >
                            {wallet.currency}
                          </Badge>
                        )}
                      </div>
                    </div>
                  </div>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon">
                        <MoreVertical className="w-4 h-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem asChild>
                        <Link href={route('treasury.wallets.show', wallet.id)}>
                          <Eye className="w-4 h-4 mr-2" />
                          View Details
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link href={route('treasury.wallets.edit', wallet.id)}>
                          <Pencil className="w-4 h-4 mr-2" />
                          Edit
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        className="text-destructive"
                        onClick={() => setDeleteWallet(wallet)}
                      >
                        <Trash2 className="w-4 h-4 mr-2" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </CardHeader>
                <CardContent>
                  <p className="text-2xl font-bold">
                    {formatCurrency(wallet.balance, wallet.currency)}
                  </p>
                  {wallet.description && (
                    <p className="text-sm text-muted-foreground mt-1 truncate">
                      {wallet.description}
                    </p>
                  )}
                </CardContent>
              </Card>
            );
          })}
        </div>
      ) : (
        <Card>
          <CardContent className="py-12">
            <EmptyState
              icon={CreditCard}
              message="Add your first wallet to start tracking your finances."
            />
          </CardContent>
        </Card>
      )}

      <ConfirmDialog
        isOpen={!!deleteWallet}
        onCancel={() => setDeleteWallet(null)}
        title="Delete Wallet"
        message={`Are you sure you want to delete "${deleteWallet?.name}"? This action cannot be undone.`}
        onConfirm={handleDelete}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
