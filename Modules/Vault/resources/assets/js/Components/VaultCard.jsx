/**
 * VaultCard component for displaying individual vault items in a card layout
 * Shows favicon, name, username, TOTP code with countdown, and action buttons
 */
import { useDisclosure } from '@/Hooks/useDisclosure';
import { cn } from '@/Utils/utils';
import { maskCVV, maskCardNumber } from '@Vault/Utils/maskUtils';
import { capitalizeType } from '@Vault/Utils/vaultUtils';
import { router } from '@inertiajs/react';
import axios from 'axios';
import {
  Clipboard,
  CreditCard,
  Eye,
  EyeOff,
  FileText,
  Lock,
  MoreVertical,
  Server,
  Star,
  StarOff,
} from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { toast } from 'sonner';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

/**
 * Get icon component based on vault type
 */
const getTypeIcon = (type) => {
  const icons = {
    login: Lock,
    card: CreditCard,
    note: FileText,
    server: Server,
  };
  return icons[type] || Lock;
};

/**
 * Get type badge color
 */
const getTypeColor = (type) => {
  const colors = {
    login: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    card: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    note: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    server: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  };
  return colors[type] || colors.login;
};

/**
 * UI constants for consistent card layout and TOTP behavior
 */
const CARD_MIN_HEIGHT = 140;
const TOTP_PERIOD = 30;
const TOTP_FETCH_INTERVAL = 5000;
const COUNTDOWN_UPDATE_INTERVAL = 1000;

export default function VaultCard({ vault }) {
  /**
   * Visibility states for sensitive data fields.
   * Each field type has its own state to allow independent show/hide controls.
   */
  const [isPasswordVisible, setIsPasswordVisible] = useState(false);
  const [isCardDetailsVisible, setIsCardDetailsVisible] = useState(false);
  const [isTotpCodeVisible, setIsTotpCodeVisible] = useState(false);
  const [totpCode, setTotpCode] = useState(null);
  const [timeRemaining, setTimeRemaining] = useState(TOTP_PERIOD);
  const [isTogglingFavorite, setIsTogglingFavorite] = useState(false);
  const deleteDialog = useDisclosure();

  const TypeIcon = getTypeIcon(vault.type);

  /**
   * Fetch TOTP codes from server and maintain countdown timer.
   * TOTP generation happens server-side to prevent secret exposure.
   */
  useEffect(() => {
    if (!vault.totp_secret || !vault.id) {
      return;
    }

    let codeIntervalId = null;
    let countdownIntervalId = null;

    const fetchTotpCode = async () => {
      try {
        const response = await axios.get(route('vault.generate-totp', { vault: vault.id }));
        if (response.data.code) {
          setTotpCode(response.data.code);
        }
      } catch {
        setTotpCode(null);
      }
    };

    const updateCountdown = () => {
      const now = Math.floor(Date.now() / 1000);
      const timeStep = Math.floor(now / TOTP_PERIOD);
      const nextStep = (timeStep + 1) * TOTP_PERIOD;
      const remaining = nextStep - now;
      setTimeRemaining(remaining);
    };

    fetchTotpCode();
    updateCountdown();

    // eslint-disable-next-line no-undef
    codeIntervalId = setInterval(fetchTotpCode, TOTP_FETCH_INTERVAL);
    // eslint-disable-next-line no-undef
    countdownIntervalId = setInterval(updateCountdown, COUNTDOWN_UPDATE_INTERVAL);

    return () => {
      if (codeIntervalId) {
        // eslint-disable-next-line no-undef
        clearInterval(codeIntervalId);
      }
      if (countdownIntervalId) {
        // eslint-disable-next-line no-undef
        clearInterval(countdownIntervalId);
      }
    };
  }, [vault.totp_secret, vault.id]);

  const handleCopyPassword = useCallback(() => {
    if (vault.value) {
      // eslint-disable-next-line no-undef
      navigator.clipboard.writeText(vault.value);
      toast.success('Password copied to clipboard');
    }
  }, [vault.value]);

  const handleCopyUsername = useCallback(() => {
    if (vault.username) {
      // eslint-disable-next-line no-undef
      navigator.clipboard.writeText(vault.username);
      toast.success('Username copied to clipboard');
    }
  }, [vault.username]);

  const handleCopyTotp = useCallback(() => {
    if (totpCode) {
      // eslint-disable-next-line no-undef
      navigator.clipboard.writeText(totpCode);
      toast.success('TOTP code copied to clipboard');
    }
  }, [totpCode]);

  const handleCopyCardNumber = useCallback(() => {
    if (vault.fields?.card_number) {
      // eslint-disable-next-line no-undef
      navigator.clipboard.writeText(vault.fields.card_number.replace(/\s/g, ''));
      toast.success('Card number copied to clipboard');
    }
  }, [vault.fields]);

  const handleCopyHost = useCallback(() => {
    if (vault.fields?.host) {
      // eslint-disable-next-line no-undef
      navigator.clipboard.writeText(vault.fields.host);
      toast.success('Host copied to clipboard');
    }
  }, [vault.fields]);

  const handleToggleFavorite = useCallback(() => {
    setIsTogglingFavorite(true);
    router.post(
      route('vault.toggle-favorite', { vault: vault.id }),
      {},
      {
        preserveScroll: true,
        preserveState: true,
        only: ['vaults'],
        skipLoadingIndicator: true,
        onSuccess: () => {
          toast.success(vault.is_favorite ? 'Removed from favorites' : 'Added to favorites');
        },
        onFinish: () => {
          setIsTogglingFavorite(false);
        },
      }
    );
  }, [vault.id, vault.is_favorite]);

  const handleDelete = useCallback(() => {
    router.delete(route('vault.destroy', { vault: vault.id }), {
      onSuccess: () => {
        deleteDialog.onClose();
        // Toast handled by Signal notification via WebSocket broadcast
      },
    });
  }, [vault.id, deleteDialog]);

  const progressPercentage = ((TOTP_PERIOD - timeRemaining) / TOTP_PERIOD) * 100;

  return (
    <>
      <Card className="group flex h-full w-full flex-col transition-all duration-200 hover:shadow-lg">
        <CardHeader className="pb-4 border-b">
          <div className="flex items-start gap-4">
            <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 ring-1 ring-primary/20 transition-transform group-hover:scale-105">
              {vault.favicon_url ? (
                <img
                  src={vault.favicon_url}
                  alt=""
                  className="h-8 w-8 rounded"
                  onError={(e) => {
                    e.target.style.display = 'none';
                    e.target.nextSibling.style.display = 'block';
                  }}
                />
              ) : null}
              <TypeIcon
                className="h-7 w-7 text-primary"
                style={{ display: vault.favicon_url ? 'none' : 'block' }}
              />
            </div>
            <div className="flex-1 min-w-0 space-y-2">
              <div className="flex items-start justify-between gap-2 min-w-0">
                <CardTitle className="text-lg font-semibold leading-tight truncate min-w-0 line-clamp-2">
                  {vault.name}
                </CardTitle>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 shrink-0"
                  onClick={handleToggleFavorite}
                  disabled={isTogglingFavorite}
                  aria-label={vault.is_favorite ? 'Remove from favorites' : 'Add to favorites'}
                >
                  {vault.is_favorite ? (
                    <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                  ) : (
                    <StarOff className="h-4 w-4" />
                  )}
                </Button>
              </div>
              <div className="flex flex-wrap gap-1.5">
                <Badge className={cn('text-xs', getTypeColor(vault.type))}>
                  {capitalizeType(vault.type)}
                </Badge>
                {vault.category && (
                  <Badge variant="outline" className="text-xs">
                    {vault.category.name}
                  </Badge>
                )}
              </div>
            </div>
          </div>
        </CardHeader>

        <CardContent className="flex-1 space-y-4 pb-4 pt-4">
          {vault.username && (
            <div className="space-y-1">
              <p className="text-xs font-medium text-muted-foreground">Username</p>
              <p className="text-sm font-mono truncate">{vault.username}</p>
            </div>
          )}

          {vault.type === 'login' && vault.url && (
            <div className="space-y-1">
              <p className="text-xs font-medium text-muted-foreground">URL</p>
              <a
                href={vault.url}
                target="_blank"
                rel="noopener noreferrer"
                className="text-sm text-primary hover:underline truncate block"
              >
                {vault.url}
              </a>
            </div>
          )}

          {vault.type === 'note' && vault.value && (
            <div className="flex flex-col rounded-lg border bg-muted/30 p-3 min-h-[140px]">
              <div className="flex items-center justify-between mb-2">
                <p className="text-xs font-medium text-muted-foreground">Content</p>
                <Button
                  variant="ghost"
                  size="sm"
                  className="h-6 px-2 text-xs shrink-0"
                  onClick={handleCopyPassword}
                  title="Copy content"
                  aria-label="Copy content"
                >
                  <Clipboard className="h-3 w-3" />
                </Button>
              </div>
              <div className="flex-1 overflow-hidden">
                <p className="text-sm whitespace-pre-wrap break-words line-clamp-5">
                  {vault.value}
                </p>
              </div>
            </div>
          )}

          {/* Credit Card Fields */}
          {vault.type === 'card' && vault.fields && (
            <div className="space-y-3 rounded-lg border bg-muted/30 p-3 min-h-[140px]">
              {/* Cardholder Name */}
              {vault.fields.cardholder_name && (
                <div>
                  <div className="flex items-center justify-between mb-1">
                    <p className="text-xs text-muted-foreground">Cardholder Name</p>
                    <div className="flex items-center gap-1">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-6 px-2 text-xs"
                        onClick={() => setIsCardDetailsVisible(!isCardDetailsVisible)}
                        title={isCardDetailsVisible ? 'Hide card number' : 'Show card number'}
                        aria-label={isCardDetailsVisible ? 'Hide card number' : 'Show card number'}
                      >
                        {isCardDetailsVisible ? (
                          <EyeOff className="h-3 w-3" />
                        ) : (
                          <Eye className="h-3 w-3" />
                        )}
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-6 px-2 text-xs shrink-0"
                        onClick={handleCopyCardNumber}
                        title="Copy card number"
                        aria-label="Copy card number"
                      >
                        <Clipboard className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                  <p className="text-sm font-medium">{vault.fields.cardholder_name}</p>
                </div>
              )}

              {/* Card Number */}
              {vault.fields.card_number && (
                <div className="space-y-1">
                  <p className="text-xs text-muted-foreground">Card Number</p>
                  <p className="text-sm font-mono">
                    {isCardDetailsVisible
                      ? vault.fields.card_number
                      : maskCardNumber(vault.fields.card_number)}
                  </p>
                </div>
              )}

              {/* Expiration and CVV */}
              <div className="grid grid-cols-2 gap-4">
                {(vault.fields.expiration_month || vault.fields.expiration_year) && (
                  <div className="space-y-1">
                    <p className="text-xs text-muted-foreground">Expiration</p>
                    <p className="text-sm font-mono">
                      {vault.fields.expiration_month || 'MM'}/{vault.fields.expiration_year || 'YY'}
                    </p>
                  </div>
                )}
                {vault.fields.cvv && (
                  <div className="space-y-1">
                    <p className="text-xs text-muted-foreground">CVV</p>
                    <p className="text-sm font-mono">
                      {isCardDetailsVisible ? vault.fields.cvv : maskCVV(vault.fields.cvv)}
                    </p>
                  </div>
                )}
              </div>

              {/* Billing Address */}
              {vault.fields.billing_address && (
                <div className="space-y-1 pt-2 border-t">
                  <p className="text-xs text-muted-foreground">Billing Address</p>
                  <p className="text-sm whitespace-pre-wrap break-words line-clamp-3">
                    {vault.fields.billing_address}
                  </p>
                </div>
              )}
            </div>
          )}

          {vault.type === 'server' && vault.fields && (
            <div className="space-y-3 rounded-lg border bg-muted/30 p-3 min-h-[140px]">
              {vault.fields.host && (
                <div className="space-y-1">
                  <div className="flex items-center justify-between">
                    <p className="text-xs text-muted-foreground">Host / IP</p>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-6 px-2 text-xs"
                      onClick={handleCopyHost}
                      title="Copy host"
                      aria-label="Copy host"
                    >
                      <Clipboard className="h-3 w-3" />
                    </Button>
                  </div>
                  <p className="text-sm font-mono">{vault.fields.host}</p>
                </div>
              )}
              {(vault.fields.port || vault.fields.protocol) && (
                <div className="flex gap-4">
                  {vault.fields.port && (
                    <div className="space-y-1">
                      <p className="text-xs text-muted-foreground">Port</p>
                      <p className="text-sm font-mono">{vault.fields.port}</p>
                    </div>
                  )}
                  {vault.fields.protocol && (
                    <div className="space-y-1">
                      <p className="text-xs text-muted-foreground">Protocol</p>
                      <p className="text-sm uppercase">{vault.fields.protocol}</p>
                    </div>
                  )}
                </div>
              )}
              {vault.fields.server_notes && (
                <div className="space-y-1">
                  <p className="text-xs text-muted-foreground">Notes</p>
                  <p className="text-sm whitespace-pre-wrap break-words line-clamp-3">
                    {vault.fields.server_notes}
                  </p>
                </div>
              )}
            </div>
          )}

          {vault.totp_secret && totpCode && (
            <div className="space-y-2 rounded-lg border bg-muted/30 p-3 min-h-[140px]">
              <div className="flex items-center justify-between">
                <p className="text-xs font-medium text-muted-foreground">TOTP Code</p>
                <div className="flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-6 px-2 text-xs"
                    onClick={() => setIsTotpCodeVisible(!isTotpCodeVisible)}
                    title={isTotpCodeVisible ? 'Hide TOTP code' : 'Show TOTP code'}
                    aria-label={isTotpCodeVisible ? 'Hide TOTP code' : 'Show TOTP code'}
                  >
                    {isTotpCodeVisible ? (
                      <EyeOff className="h-3 w-3" />
                    ) : (
                      <Eye className="h-3 w-3" />
                    )}
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    className="h-6 px-2 text-xs"
                    onClick={handleCopyTotp}
                    title="Copy TOTP code"
                    aria-label="Copy TOTP code"
                  >
                    <Clipboard className="h-3 w-3" />
                  </Button>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <span className="text-2xl font-mono font-bold tracking-wider">
                  {isTotpCodeVisible ? totpCode : '******'}
                </span>
              </div>
              <div className="space-y-1">
                <div className="flex items-center justify-between text-xs">
                  <span className="text-muted-foreground">Time remaining</span>
                  <span className="font-mono">{timeRemaining}s</span>
                </div>
                <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                  <div
                    className="h-full bg-primary transition-all duration-1000"
                    style={{ width: `${progressPercentage}%` }}
                  />
                </div>
              </div>
            </div>
          )}
        </CardContent>

        <CardFooter className="flex items-center justify-end gap-2 border-t border-border/50 pt-4">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="h-8 w-8">
                <MoreVertical className="h-4 w-4" />
                <span className="sr-only">More actions</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {(vault.type === 'login' || vault.type === 'server') && (
                <DropdownMenuItem onClick={handleCopyPassword}>
                  <Clipboard className="mr-2 h-4 w-4" />
                  Copy Password
                </DropdownMenuItem>
              )}
              {vault.type === 'note' && (
                <DropdownMenuItem onClick={handleCopyPassword}>
                  <Clipboard className="mr-2 h-4 w-4" />
                  Copy Content
                </DropdownMenuItem>
              )}
              {vault.username && (
                <DropdownMenuItem onClick={handleCopyUsername}>
                  <Clipboard className="mr-2 h-4 w-4" />
                  Copy Username
                </DropdownMenuItem>
              )}
              {(vault.type === 'login' || vault.type === 'server') && (
                <DropdownMenuItem onClick={() => setIsPasswordVisible(!isPasswordVisible)}>
                  {isPasswordVisible ? (
                    <>
                      <EyeOff className="mr-2 h-4 w-4" />
                      Hide Password
                    </>
                  ) : (
                    <>
                      <Eye className="mr-2 h-4 w-4" />
                      Reveal Password
                    </>
                  )}
                </DropdownMenuItem>
              )}
              <DropdownMenuSeparator />
              <DropdownMenuItem asChild>
                <a href={route('vault.edit', { vault: vault.id })}>Edit</a>
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={deleteDialog.onOpen}
                className="text-destructive focus:text-destructive"
              >
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </CardFooter>
      </Card>

      <ConfirmDialog
        isOpen={deleteDialog.isOpen}
        onConfirm={handleDelete}
        onCancel={deleteDialog.onClose}
        title="Delete Vault Item"
        message={`Are you sure you want to delete "${vault.name}"? This action cannot be undone.`}
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
      />
    </>
  );
}
