/**
 * Shared form fields component for Vault Create and Edit pages - RHF Version
 * Uses React Hook Form with Controller pattern for auto-revalidation
 * Extracts common form field logic to reduce duplication
 */
import { cn } from '@/Utils/utils';
import CardNumberInputRHF from '@Vault/Components/FormFields/CardNumberInputRHF';
import VaultFormInputRHF from '@Vault/Components/FormFields/VaultFormInputRHF';
import VaultSelectRHF from '@Vault/Components/FormFields/VaultSelectRHF';
import VaultSwitchRHF from '@Vault/Components/FormFields/VaultSwitchRHF';
import QrCodeScanner from '@Vault/Components/QrCodeScanner';
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
  const { control, watch, setValue, getValues } = form;

  /**
   * Conditional field visibility flags based on selected vault type.
   * Determines which form fields should be displayed for each vault type.
   */
  const selectedType = watch('type');
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
        setValue('totp_secret', data.secret);
      }
      if (data.algorithm) {
        setValue('totp_algorithm', data.algorithm.toLowerCase());
      }
      if (data.digits) {
        setValue('totp_digits', parseInt(data.digits, 10));
      }
      if (data.period) {
        setValue('totp_period', parseInt(data.period, 10));
      }
      if (data.email && !getValues('email')) {
        setValue('email', data.email);
      }
      if (data.issuer) {
        setValue('issuer', data.issuer);
      }
      onQrScannerOpenChange(false);
    },
    [setValue, getValues, onQrScannerOpenChange]
  );

  const typeOptions = types.map((type) => ({
    value: type,
    label: capitalizeType(type),
  }));
  const categoryOptions = categories.map((category) => ({
    value: category.id,
    label: category.name,
  }));

  const monthOptions = Array.from({ length: 12 }, (_, i) => {
    const month = String(i + 1).padStart(2, '0');
    return { value: month, label: month };
  });
  const yearOptions = Array.from({ length: 20 }, (_, i) => {
    const year = new Date().getFullYear() + i;
    const yearShort = String(year).slice(-2);
    return { value: yearShort, label: `${yearShort} (${year})` };
  });

  const protocolOptions = [
    { value: 'ssh', label: 'SSH' },
    { value: 'ftp', label: 'FTP' },
    { value: 'ftps', label: 'FTPS' },
    { value: 'sftp', label: 'SFTP' },
    { value: 'rdp', label: 'RDP' },
    { value: 'vnc', label: 'VNC' },
    { value: 'telnet', label: 'Telnet' },
    { value: 'http', label: 'HTTP' },
    { value: 'https', label: 'HTTPS' },
    { value: 'mysql', label: 'MySQL' },
    { value: 'postgresql', label: 'PostgreSQL' },
    { value: 'mongodb', label: 'MongoDB' },
    { value: 'redis', label: 'Redis' },
    { value: 'other', label: 'Other' },
  ];

  return (
    <>
      {/* Type Selector */}
      <VaultSelectRHF
        name="type"
        control={control}
        label="Type"
        required
        options={typeOptions}
        placeholder="Select type"
        disabled={isEditMode}
      />

      {/* Name */}
      <VaultFormInputRHF
        name="name"
        control={control}
        label="Name"
        required
        placeholder="Enter vault item name"
      />

      {/* Username (login, server) */}
      {showUsername && (
        <VaultFormInputRHF
          name="username"
          control={control}
          label="Username"
          placeholder="Enter username"
        />
      )}

      {/* Email (login) */}
      {showEmail && (
        <VaultFormInputRHF
          name="email"
          control={control}
          label="Email"
          type="email"
          placeholder="Enter email address"
        />
      )}

      {/* Issuer (login) */}
      {showIssuer && (
        <VaultFormInputRHF
          name="issuer"
          control={control}
          label="Issuer"
          placeholder="Enter issuer (e.g., Google, GitHub, Dicoding)"
          helperText="The service or company name (automatically filled from QR code)"
        />
      )}

      {/* Value/Password/Note Content */}
      {selectedType === 'note' ? (
        <FormTextareaRHF
          name="value"
          control={control}
          label="Content"
          placeholder="Enter secure note content"
          rows={6}
        />
      ) : selectedType !== 'card' ? (
        <VaultFormInputRHF
          name="value"
          control={control}
          label="Password"
          type="password"
          placeholder="Enter password"
        >
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
        </VaultFormInputRHF>
      ) : null}

      {/* URL (login) */}
      {showUrl && (
        <VaultFormInputRHF
          name="url"
          control={control}
          label="URL"
          type="url"
          placeholder="https://example.com"
        />
      )}

      {/* TOTP Secret (login) */}
      {showTotp && (
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
          <VaultFormInputRHF
            name="totp_secret"
            control={control}
            placeholder="Enter TOTP secret key (base32 encoded) or scan QR code"
            helperText="Enter the base32-encoded secret key or scan the QR code from your authenticator app"
          />
        </div>
      )}

      {/* Card Fields */}
      {showCardFields && (
        <div className="space-y-4 rounded-lg border p-4">
          <p className="text-sm font-medium">Card Information</p>
          <p className="text-xs text-muted-foreground">
            Card details are stored securely in encrypted fields
          </p>

          {/* Card Number with auto-detection */}
          <CardNumberInputRHF
            name="fields.card_number"
            control={control}
            label="Card Number"
            onCardTypeChange={(type) => {
              if (type) {
                setValue('fields.card_type', type, { shouldValidate: false });
              }
            }}
          />

          {/* Cardholder Name */}
          <VaultFormInputRHF
            name="fields.cardholder_name"
            control={control}
            label="Cardholder Name"
            placeholder="John Doe"
            maxLength={255}
            helperText="Name as it appears on the card"
          />

          {/* Expiration Month/Year */}
          <div className="grid grid-cols-2 gap-4">
            <VaultSelectRHF
              name="fields.expiration_month"
              control={control}
              label="Expiration Month"
              options={monthOptions}
              placeholder="MM"
            />
            <VaultSelectRHF
              name="fields.expiration_year"
              control={control}
              label="Expiration Year"
              options={yearOptions}
              placeholder="YY"
            />
          </div>

          {/* CVV */}
          <VaultFormInputRHF
            name="fields.cvv"
            control={control}
            label="CVV"
            type="password"
            placeholder="123"
            maxLength={4}
            helperText="The 3 or 4 digit code on the back of your card"
          />

          {/* Billing Address */}
          <FormTextareaRHF
            name="fields.billing_address"
            control={control}
            label="Billing Address"
            placeholder="Street address, City, State, ZIP"
            rows={3}
          />
        </div>
      )}

      {/* Server Fields */}
      {showServerFields && (
        <div className="space-y-4 rounded-lg border p-4">
          <p className="text-sm font-medium">Server Information</p>
          <p className="text-xs text-muted-foreground">
            Server connection details are stored securely in encrypted fields
          </p>

          {/* Host */}
          <VaultFormInputRHF
            name="fields.host"
            control={control}
            label="Host / IP Address"
            placeholder="192.168.1.1 or example.com"
            maxLength={255}
          />

          {/* Port and Protocol */}
          <div className="grid grid-cols-2 gap-4">
            <Controller
              name="fields.port"
              control={control}
              render={({ field, fieldState: { error } }) => (
                <div className="space-y-2">
                  <Label htmlFor="fields.port">Port</Label>
                  <input
                    id="fields.port"
                    type="number"
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
                    className={cn(
                      'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                      error && 'border-destructive'
                    )}
                  />
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              )}
            />

            <VaultSelectRHF
              name="fields.protocol"
              control={control}
              label="Protocol"
              options={protocolOptions}
              placeholder="Select protocol"
            />
          </div>

          {/* Server Notes */}
          <FormTextareaRHF
            name="fields.server_notes"
            control={control}
            label="Additional Notes"
            placeholder="Additional server information, connection instructions, etc."
            rows={4}
          />
        </div>
      )}

      {/* Category */}
      <VaultSelectRHF
        name="category_id"
        control={control}
        label="Category"
        options={categoryOptions}
        placeholder="Select category (optional)"
      />

      {/* Favorite Toggle */}
      <VaultSwitchRHF
        name="is_favorite"
        control={control}
        label="Favorite"
        description="Mark this item as a favorite for quick access"
      />
    </>
  );
}
