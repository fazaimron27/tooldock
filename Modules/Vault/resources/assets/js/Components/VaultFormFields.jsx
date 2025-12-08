/**
 * Shared form fields component for Vault Create and Edit pages
 * Extracts common form field logic to reduce duplication
 */
import { cn } from '@/Utils/utils';
import CardTypeLogo from '@Vault/Components/CardTypeLogo';
import QrCodeScanner from '@Vault/Components/QrCodeScanner';
import VaultFormInput from '@Vault/Components/VaultFormInput';
import { detectCardType } from '@Vault/Utils/cardTypeDetector';
import { formatCardNumber } from '@Vault/Utils/maskUtils';
import { capitalizeType } from '@Vault/Utils/vaultUtils';
import { Key, QrCode, RefreshCw } from 'lucide-react';
import { useCallback } from 'react';
import { Controller } from 'react-hook-form';

import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

export default function VaultFormFields({
  form,
  categories = [],
  types = [],
  isGeneratingPassword = false,
  onGeneratePassword,
  qrScannerOpen = false,
  onQrScannerOpenChange,
  isEditMode = false,
}) {
  /**
   * Conditional field visibility flags based on selected vault type.
   * Determines which form fields should be displayed for each vault type.
   */
  const selectedType = form.watch('type');
  const showLoginServerFields = selectedType === 'login' || selectedType === 'server';
  const showLoginFields = selectedType === 'login';
  const showUsername = showLoginServerFields;
  const showEmail = selectedType === 'login';
  const showUrl = selectedType === 'login';
  const showTotp = showLoginFields;
  const showIssuer = selectedType === 'login';
  const showCardFields = selectedType === 'card';
  const showServerFields = selectedType === 'server';

  const handleQrScan = useCallback(
    (data) => {
      if (data.secret) {
        form.setValue('totp_secret', data.secret);
      }
      if (data.email && !form.getValues('email')) {
        form.setValue('email', data.email);
      }
      if (data.issuer) {
        form.setValue('issuer', data.issuer);
      }
      onQrScannerOpenChange(false);
    },
    [form, onQrScannerOpenChange]
  );

  return (
    <>
      <Controller
        name="type"
        control={form.control}
        render={({ field, fieldState: { error } }) => (
          <div className="space-y-2">
            <Label htmlFor="type">
              Type <span className="text-destructive">*</span>
            </Label>
            <Select value={field.value} onValueChange={field.onChange} disabled={isEditMode}>
              <SelectTrigger id="type" className={cn(error && 'border-destructive')}>
                <SelectValue placeholder="Select type" />
              </SelectTrigger>
              <SelectContent>
                {types.map((type) => (
                  <SelectItem key={type} value={type}>
                    {capitalizeType(type)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {error && <p className="text-sm text-destructive">{error.message}</p>}
          </div>
        )}
      />

      <Controller
        name="name"
        control={form.control}
        render={({ field, fieldState: { error } }) => (
          <div className="space-y-2">
            <Label htmlFor="name">
              Name <span className="text-destructive">*</span>
            </Label>
            <VaultFormInput
              id="name"
              type="text"
              {...field}
              error={!!error}
              placeholder="Enter vault item name"
              required
            />
            {error && <p className="text-sm text-destructive">{error.message}</p>}
          </div>
        )}
      />

      {showUsername && (
        <Controller
          name="username"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <Label htmlFor="username">Username</Label>
              <VaultFormInput
                id="username"
                type="text"
                {...field}
                error={!!error}
                placeholder="Enter username"
              />
              {error && <p className="text-sm text-destructive">{error.message}</p>}
            </div>
          )}
        />
      )}

      {showEmail && (
        <Controller
          name="email"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <VaultFormInput
                id="email"
                type="email"
                {...field}
                error={!!error}
                placeholder="Enter email address"
              />
              {error && <p className="text-sm text-destructive">{error.message}</p>}
            </div>
          )}
        />
      )}

      {showIssuer && (
        <Controller
          name="issuer"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <Label htmlFor="issuer">Issuer</Label>
              <VaultFormInput
                id="issuer"
                type="text"
                {...field}
                error={!!error}
                placeholder="Enter issuer (e.g., Google, GitHub, Dicoding)"
              />
              {error && <p className="text-sm text-destructive">{error.message}</p>}
              <p className="text-xs text-muted-foreground">
                The service or company name (automatically filled from QR code)
              </p>
            </div>
          )}
        />
      )}

      {selectedType === 'note' ? (
        <FormTextareaRHF
          name="value"
          control={form.control}
          label="Content"
          placeholder="Enter secure note content"
          rows={6}
        />
      ) : selectedType !== 'card' ? (
        <Controller
          name="value"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <Label htmlFor="value">Password</Label>
              <div className="flex gap-2">
                <VaultFormInput
                  id="value"
                  type="password"
                  {...field}
                  error={!!error}
                  placeholder="Enter password"
                  className="flex-1"
                />
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  onClick={onGeneratePassword}
                  disabled={isGeneratingPassword}
                  title="Generate secure password"
                >
                  {isGeneratingPassword ? (
                    <RefreshCw className="h-4 w-4 animate-spin" />
                  ) : (
                    <Key className="h-4 w-4" />
                  )}
                </Button>
              </div>
              {error && <p className="text-sm text-destructive">{error.message}</p>}
            </div>
          )}
        />
      ) : null}

      {showUrl && (
        <Controller
          name="url"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <Label htmlFor="url">URL</Label>
              <VaultFormInput
                id="url"
                type="url"
                {...field}
                error={!!error}
                placeholder="https://example.com"
              />
              {error && <p className="text-sm text-destructive">{error.message}</p>}
            </div>
          )}
        />
      )}

      {showTotp && (
        <Controller
          name="totp_secret"
          control={form.control}
          render={({ field, fieldState: { error } }) => (
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label htmlFor="totp_secret">TOTP Secret</Label>
                <Dialog open={qrScannerOpen} onOpenChange={onQrScannerOpenChange}>
                  <DialogTrigger asChild>
                    <Button type="button" variant="outline" size="sm">
                      <QrCode className="h-4 w-4 mr-2" />
                      Scan QR Code
                    </Button>
                  </DialogTrigger>
                  {qrScannerOpen && (
                    <DialogContent className="max-w-md" key="qr-scanner">
                      <DialogTitle className="sr-only">Scan QR Code for TOTP</DialogTitle>
                      <DialogDescription className="sr-only">
                        Scan a QR code from your authenticator app to automatically extract the TOTP
                        secret
                      </DialogDescription>
                      <QrCodeScanner
                        onScan={handleQrScan}
                        onClose={() => onQrScannerOpenChange(false)}
                      />
                    </DialogContent>
                  )}
                </Dialog>
              </div>
              <VaultFormInput
                id="totp_secret"
                type="text"
                {...field}
                error={!!error}
                placeholder="Enter TOTP secret key (base32 encoded) or scan QR code"
              />
              {error && <p className="text-sm text-destructive">{error.message}</p>}
              <p className="text-xs text-muted-foreground">
                Enter the base32-encoded secret key or scan the QR code from your authenticator app
              </p>
            </div>
          )}
        />
      )}

      {showCardFields && (
        <div className="space-y-4 rounded-lg border p-4">
          <p className="text-sm font-medium">Card Information</p>
          <p className="text-xs text-muted-foreground">
            Card details are stored securely in encrypted fields
          </p>

          <Controller
            name="fields.card_number"
            control={form.control}
            render={({ field, fieldState: { error } }) => {
              const cardTypeValue = form.watch('fields.card_type');
              const detectedType = detectCardType(field.value);

              return (
                <div className="space-y-2">
                  <Label htmlFor="card_number">Card Number</Label>
                  <div className="relative">
                    <VaultFormInput
                      id="card_number"
                      type="text"
                      {...field}
                      value={field.value || ''}
                      onChange={(e) => {
                        const formatted = formatCardNumber(e.target.value);
                        field.onChange(formatted);

                        const detected = detectCardType(formatted);
                        if (detected && (!cardTypeValue || cardTypeValue !== detected)) {
                          form.setValue('fields.card_type', detected, { shouldValidate: false });
                        }
                      }}
                      placeholder="1234 5678 9012 3456"
                      maxLength={23}
                      error={!!error}
                      className={cn(detectedType && 'pr-12')}
                    />
                    {detectedType && (
                      <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center pointer-events-none">
                        <CardTypeLogo cardType={detectedType} size={28} />
                      </div>
                    )}
                  </div>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              );
            }}
          />

          {/* Cardholder Name */}
          <Controller
            name="fields.cardholder_name"
            control={form.control}
            render={({ field, fieldState: { error } }) => (
              <div className="space-y-2">
                <Label htmlFor="cardholder_name">Cardholder Name</Label>
                <VaultFormInput
                  id="cardholder_name"
                  type="text"
                  {...field}
                  value={field.value || ''}
                  placeholder="John Doe"
                  maxLength={255}
                  error={!!error}
                />
                {error && <p className="text-sm text-destructive">{error.message}</p>}
                <p className="text-xs text-muted-foreground">Name as it appears on the card</p>
              </div>
            )}
          />

          <div className="grid grid-cols-2 gap-4">
            <Controller
              name="fields.expiration_month"
              control={form.control}
              render={({ field, fieldState: { error } }) => (
                <div className="space-y-2">
                  <Label htmlFor="expiration_month">Expiration Month</Label>
                  <Select value={field.value || ''} onValueChange={field.onChange}>
                    <SelectTrigger
                      id="expiration_month"
                      className={cn(error && 'border-destructive')}
                    >
                      <SelectValue placeholder="MM" />
                    </SelectTrigger>
                    <SelectContent>
                      {Array.from({ length: 12 }, (_, i) => {
                        const month = String(i + 1).padStart(2, '0');
                        return (
                          <SelectItem key={month} value={month}>
                            {month}
                          </SelectItem>
                        );
                      })}
                    </SelectContent>
                  </Select>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              )}
            />

            <Controller
              name="fields.expiration_year"
              control={form.control}
              render={({ field, fieldState: { error } }) => (
                <div className="space-y-2">
                  <Label htmlFor="expiration_year">Expiration Year</Label>
                  <Select value={field.value || ''} onValueChange={field.onChange}>
                    <SelectTrigger
                      id="expiration_year"
                      className={cn(error && 'border-destructive')}
                    >
                      <SelectValue placeholder="YY" />
                    </SelectTrigger>
                    <SelectContent>
                      {Array.from({ length: 20 }, (_, i) => {
                        const year = new Date().getFullYear() + i;
                        const yearShort = String(year).slice(-2);
                        return (
                          <SelectItem key={year} value={yearShort}>
                            {yearShort} ({year})
                          </SelectItem>
                        );
                      })}
                    </SelectContent>
                  </Select>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              )}
            />
          </div>

          <Controller
            name="fields.cvv"
            control={form.control}
            render={({ field, fieldState: { error } }) => (
              <div className="space-y-2">
                <Label htmlFor="cvv">CVV</Label>
                <VaultFormInput
                  id="cvv"
                  type="password"
                  {...field}
                  value={field.value || ''}
                  placeholder="123"
                  maxLength={4}
                  error={!!error}
                />
                {error && <p className="text-sm text-destructive">{error.message}</p>}
                <p className="text-xs text-muted-foreground">
                  The 3 or 4 digit code on the back of your card
                </p>
              </div>
            )}
          />

          <FormTextareaRHF
            name="fields.billing_address"
            control={form.control}
            label="Billing Address"
            placeholder="Street address, City, State, ZIP"
            rows={3}
          />
        </div>
      )}

      {showServerFields && (
        <div className="space-y-4 rounded-lg border p-4">
          <p className="text-sm font-medium">Server Information</p>
          <p className="text-xs text-muted-foreground">
            Server connection details are stored securely in encrypted fields
          </p>

          <Controller
            name="fields.host"
            control={form.control}
            render={({ field, fieldState: { error } }) => (
              <div className="space-y-2">
                <Label htmlFor="host">Host / IP Address</Label>
                <VaultFormInput
                  id="host"
                  type="text"
                  {...field}
                  value={field.value || ''}
                  placeholder="192.168.1.1 or example.com"
                  maxLength={255}
                  error={!!error}
                />
                {error && <p className="text-sm text-destructive">{error.message}</p>}
              </div>
            )}
          />

          <div className="grid grid-cols-2 gap-4">
            <Controller
              name="fields.port"
              control={form.control}
              render={({ field, fieldState: { error } }) => (
                <div className="space-y-2">
                  <Label htmlFor="port">Port</Label>
                  <VaultFormInput
                    id="port"
                    type="number"
                    {...field}
                    value={field.value || ''}
                    onChange={(e) => {
                      const value = e.target.value;
                      if (value === '' || (Number(value) >= 1 && Number(value) <= 65535)) {
                        field.onChange(value);
                      }
                    }}
                    placeholder="22"
                    min={1}
                    max={65535}
                    error={!!error}
                  />
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              )}
            />

            <Controller
              name="fields.protocol"
              control={form.control}
              render={({ field, fieldState: { error } }) => (
                <div className="space-y-2">
                  <Label htmlFor="protocol">Protocol</Label>
                  <Select value={field.value || ''} onValueChange={field.onChange}>
                    <SelectTrigger id="protocol" className={cn(error && 'border-destructive')}>
                      <SelectValue placeholder="Select protocol" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="ssh">SSH</SelectItem>
                      <SelectItem value="ftp">FTP</SelectItem>
                      <SelectItem value="ftps">FTPS</SelectItem>
                      <SelectItem value="sftp">SFTP</SelectItem>
                      <SelectItem value="rdp">RDP</SelectItem>
                      <SelectItem value="vnc">VNC</SelectItem>
                      <SelectItem value="telnet">Telnet</SelectItem>
                      <SelectItem value="http">HTTP</SelectItem>
                      <SelectItem value="https">HTTPS</SelectItem>
                      <SelectItem value="mysql">MySQL</SelectItem>
                      <SelectItem value="postgresql">PostgreSQL</SelectItem>
                      <SelectItem value="mongodb">MongoDB</SelectItem>
                      <SelectItem value="redis">Redis</SelectItem>
                      <SelectItem value="other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              )}
            />
          </div>

          <FormTextareaRHF
            name="fields.server_notes"
            control={form.control}
            label="Additional Notes"
            placeholder="Additional server information, connection instructions, etc."
            rows={4}
          />
        </div>
      )}

      <Controller
        name="category_id"
        control={form.control}
        render={({ field, fieldState: { error } }) => (
          <div className="space-y-2">
            <Label htmlFor="category_id">Category</Label>
            <Select
              value={field.value || undefined}
              onValueChange={(value) => field.onChange(value || '')}
            >
              <SelectTrigger id="category_id" className={cn(error && 'border-destructive')}>
                <SelectValue placeholder="Select category (optional)" />
              </SelectTrigger>
              <SelectContent>
                {categories.map((category) => (
                  <SelectItem key={category.id} value={category.id}>
                    {category.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {error && <p className="text-sm text-destructive">{error.message}</p>}
          </div>
        )}
      />

      <Controller
        name="is_favorite"
        control={form.control}
        render={({ field }) => (
          <div className="flex items-center justify-between rounded-lg border p-4">
            <div className="space-y-0.5">
              <Label htmlFor="is_favorite">Favorite</Label>
              <p className="text-xs text-muted-foreground">
                Mark this item as a favorite for quick access
              </p>
            </div>
            <Switch id="is_favorite" checked={field.value} onCheckedChange={field.onChange} />
          </div>
        )}
      />
    </>
  );
}
