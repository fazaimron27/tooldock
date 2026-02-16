/**
 * Transaction Show page - Enhanced transaction details view
 * Features: Premium card design, category icons, smooth animations
 */
import { useAppearance } from '@/Hooks/useAppearance';
import { formatDate } from '@/Utils/format';
import { cn } from '@/Utils/utils';
import { getCategoryIcon, typeConfig } from '@Treasury/Components/TransactionItem';
import { Link, router } from '@inertiajs/react';
import {
  ArrowDownCircle,
  ArrowLeft,
  ArrowRightCircle,
  ArrowUpCircle,
  Calendar,
  ChevronRight,
  CreditCard,
  Download,
  FileIcon,
  FileText,
  Paperclip,
  Pencil,
  Tag,
  Target,
  Trash2,
  Wallet,
} from 'lucide-react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';

export default function Show({ transaction }) {
  const { formatCurrency } = useAppearance();
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const config = typeConfig[transaction.type] || typeConfig.expense;
  const TypeIcon = config.icon;
  const CategoryIcon = getCategoryIcon(transaction.category?.slug);
  const categoryColor = transaction.category?.color || '#6b7280';

  const handleDelete = () => {
    router.delete(route('treasury.transactions.destroy', transaction.id), {
      onSuccess: () => {
        // Will redirect to index
      },
    });
  };

  const DetailRow = ({ icon: Icon, label, value, linkTo, badge, color }) => (
    <div className="flex items-start gap-4 py-3.5 group">
      <div
        className={cn(
          'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-all group-hover:scale-105',
          !color && 'bg-muted'
        )}
        style={{
          backgroundColor: color ? `${color}15` : undefined,
        }}
      >
        <Icon className="w-4 h-4 text-muted-foreground" style={color ? { color } : undefined} />
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">
          {label}
        </p>
        {linkTo ? (
          <Link
            href={linkTo}
            className="font-medium text-primary hover:underline inline-flex items-center gap-1 group/link"
          >
            {value}
            <ChevronRight className="w-3 h-3 opacity-0 group-hover/link:opacity-100 transition-opacity" />
          </Link>
        ) : badge ? (
          <div className="flex items-center gap-2">
            {badge.color && (
              <span
                className="w-3 h-3 rounded-full shrink-0"
                style={{ backgroundColor: badge.color }}
              />
            )}
            <span
              className="px-2.5 py-1 rounded-lg text-sm font-medium"
              style={{
                backgroundColor: badge.color ? `${badge.color}15` : undefined,
                color: badge.color,
              }}
            >
              {value}
            </span>
          </div>
        ) : (
          <p className="font-medium text-foreground">{value}</p>
        )}
      </div>
    </div>
  );

  return (
    <PageShell
      title="Transaction Details"
      backLink={route('treasury.transactions.index')}
      backLabel="Back to Transactions"
      actions={
        <div className="flex flex-wrap md:flex-nowrap items-center gap-2 w-full md:w-auto">
          <Link href={route('treasury.transactions.index')} className="hidden md:block">
            <Button variant="outline">
              <ArrowLeft className="w-4 h-4 mr-2" />
              Back
            </Button>
          </Link>
          <Button
            variant="outline"
            onClick={() => setShowDeleteConfirm(true)}
            className="flex-1 md:flex-initial text-destructive hover:text-destructive hover:bg-destructive/10"
          >
            <Trash2 className="w-4 h-4 mr-2" />
            Delete
          </Button>
          <Link
            href={route('treasury.transactions.edit', transaction.id)}
            className="flex-1 md:flex-initial"
          >
            <Button className="w-full">
              <Pencil className="w-4 h-4 mr-2" />
              Edit
            </Button>
          </Link>
        </div>
      }
    >
      <div className="max-w-2xl space-y-6">
        {/* Hero Card - Amount & Type */}
        <Card className="overflow-hidden border-0 shadow-lg md:shadow-xl">
          {/* Gradient background based on type */}
          <div
            className={cn(
              'relative p-6 md:p-8 overflow-hidden',
              transaction.type === 'income' && 'bg-gradient-to-br from-emerald-500 to-emerald-600',
              transaction.type === 'expense' && 'bg-gradient-to-br from-rose-500 to-rose-600',
              transaction.type === 'transfer' && 'bg-gradient-to-br from-blue-500 to-blue-600'
            )}
          >
            {/* Background pattern */}
            <div className="absolute inset-0 opacity-10">
              <svg className="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                  <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                    <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" strokeWidth="0.5" />
                  </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)" />
              </svg>
            </div>

            {/* Floating circles decoration */}
            <div className="absolute -right-8 -top-8 w-32 h-32 bg-white/10 rounded-full blur-2xl" />
            <div className="absolute -left-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl" />

            <div className="relative flex items-center gap-4 md:gap-5">
              {/* Type icon */}
              <div className="w-12 h-12 md:w-16 md:h-16 rounded-xl md:rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center shrink-0">
                <TypeIcon className="w-6 h-6 md:w-8 md:h-8 text-white" />
              </div>

              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <Badge
                    variant="secondary"
                    className="bg-white/20 text-white hover:bg-white/30 capitalize text-[10px] md:text-xs"
                  >
                    {transaction.type}
                  </Badge>
                </div>
                <p className="text-2xl md:text-4xl font-bold text-white tracking-tight truncate">
                  {config.prefix}
                  {formatCurrency(transaction.amount, transaction.wallet?.currency)}
                </p>
              </div>
            </div>
          </div>

          {/* Quick info strip */}
          <div className="px-5 py-3 md:px-6 md:py-4 bg-muted/30 border-t flex flex-wrap items-center justify-between gap-2 text-xs md:text-sm">
            <div className="flex items-center gap-2 text-muted-foreground">
              <Calendar className="w-3.5 h-3.5 md:w-4 md:h-4" />
              <span>{formatDate(transaction.date, 'datetime')}</span>
            </div>
            {transaction.wallet && (
              <div className="flex items-center gap-2 text-muted-foreground">
                <Wallet className="w-3.5 h-3.5 md:w-4 md:h-4" />
                <span>{transaction.wallet.name}</span>
              </div>
            )}
          </div>
        </Card>

        {/* Details Card */}
        <Card className="shadow-sm">
          <CardContent className="p-6 divide-y divide-border/50">
            {/* Description */}
            {transaction.description && (
              <DetailRow icon={FileText} label="Description" value={transaction.description} />
            )}

            {/* Date */}
            <DetailRow icon={Calendar} label="Date" value={formatDate(transaction.date, 'full')} />

            {/* Wallet */}
            {transaction.wallet && (
              <DetailRow
                icon={Wallet}
                label="Wallet"
                value={transaction.wallet.name}
                linkTo={route('treasury.wallets.show', transaction.wallet.id)}
              />
            )}

            {/* Category */}
            {transaction.category && (
              <div className="flex items-start gap-4 py-3.5 group">
                <div
                  className="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-all group-hover:scale-105"
                  style={{ backgroundColor: `${categoryColor}15` }}
                >
                  <CategoryIcon className="w-4 h-4" style={{ color: categoryColor }} />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">
                    Category
                  </p>
                  <div className="flex items-center gap-2">
                    <span
                      className="w-3 h-3 rounded-full shrink-0"
                      style={{ backgroundColor: categoryColor }}
                    />
                    <span
                      className="px-2.5 py-1 rounded-lg text-sm font-medium"
                      style={{
                        backgroundColor: `${categoryColor}15`,
                        color: categoryColor,
                      }}
                    >
                      {transaction.category.name}
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* Goal */}
            {transaction.goal && (
              <DetailRow
                icon={Target}
                label="Goal"
                value={transaction.goal.name}
                linkTo={route('treasury.goals.show', transaction.goal.id)}
              />
            )}

            {/* Transfer Info */}
            {transaction.type === 'transfer' && transaction.destination_wallet && (
              <DetailRow
                icon={ArrowRightCircle}
                label="Transfer To"
                value={transaction.destination_wallet.name}
                linkTo={route('treasury.wallets.show', transaction.destination_wallet.id)}
              />
            )}
          </CardContent>
        </Card>

        {/* Attachments Section */}
        {transaction.attachments && transaction.attachments.length > 0 && (
          <Card className="shadow-sm">
            <CardContent className="p-6">
              <div className="flex items-center gap-2 mb-4">
                <Paperclip className="w-4 h-4 text-muted-foreground" />
                <h3 className="font-medium">Attachments ({transaction.attachments.length})</h3>
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                {transaction.attachments.map((file) => {
                  const isImage = file.mime_type?.startsWith('image/');
                  return (
                    <a
                      key={file.id}
                      href={file.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="group relative block rounded-lg border overflow-hidden hover:border-primary transition-colors"
                    >
                      {isImage ? (
                        <div className="aspect-square bg-muted">
                          <img
                            src={file.url}
                            alt={file.filename}
                            className="w-full h-full object-cover"
                          />
                        </div>
                      ) : (
                        <div className="aspect-square bg-muted/50 flex flex-col items-center justify-center p-4">
                          <FileIcon className="w-8 h-8 text-muted-foreground mb-2" />
                          <p className="text-xs text-muted-foreground truncate max-w-full px-2">
                            {file.filename}
                          </p>
                        </div>
                      )}
                      <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                        <Download className="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                      </div>
                    </a>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Metadata Footer */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-y-1 text-[10px] md:text-xs text-muted-foreground px-1">
          <span>Created {formatDate(transaction.created_at, 'full')}</span>
          {transaction.updated_at !== transaction.created_at && (
            <span>Updated {formatDate(transaction.updated_at, 'full')}</span>
          )}
        </div>
      </div>

      <ConfirmDialog
        isOpen={showDeleteConfirm}
        onCancel={() => setShowDeleteConfirm(false)}
        title="Delete Transaction"
        message="Are you sure you want to delete this transaction? This action cannot be undone and will affect your wallet balance."
        onConfirm={handleDelete}
        confirmLabel="Delete Transaction"
        variant="destructive"
      />
    </PageShell>
  );
}
