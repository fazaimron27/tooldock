/**
 * Settings management page with grouped accordion sections and dynamic form fields
 * Allows admins to manage application settings organized by groups
 * Uses URL query parameters for accordion sections to ensure proper flash message handling
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { cn } from '@/Utils/utils';
import { updateSettingsResolver } from '@Settings/Schemas/settingsSchemas';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextareaRHF from '@/Components/Common/FormTextareaRHF';
import SearchableSelectRHF from '@/Components/Common/SearchableSelectRHF';
import PageShell from '@/Components/Layouts/PageShell';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/Components/ui/accordion';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Slider } from '@/Components/ui/slider';
import { Switch } from '@/Components/ui/switch';

/**
 * Maps currency codes to their display symbols
 */
const CURRENCY_SYMBOLS = {
  IDR: 'Rp',
  MYR: 'RM',
  USD: '$',
  EUR: '€',
  GBP: '£',
  JPY: '¥',
  CNY: '¥',
  SGD: 'S$',
  AUD: 'A$',
  THB: '฿',
  PHP: '₱',
  VND: '₫',
  INR: '₹',
  KRW: '₩',
};

/**
 * Maps currency codes to their locales for number formatting
 */
const CURRENCY_LOCALES = {
  IDR: 'id-ID', // 1.000.000 (periods)
  MYR: 'ms-MY', // 1,000,000 (commas)
  USD: 'en-US', // 1,000,000 (commas)
  EUR: 'de-DE', // 1.000.000 (periods)
  GBP: 'en-GB', // 1,000,000 (commas)
  JPY: 'ja-JP', // 1,000,000 (commas)
  CNY: 'zh-CN', // 1,000,000 (commas)
  SGD: 'en-SG', // 1,000,000 (commas)
  AUD: 'en-AU', // 1,000,000 (commas)
  THB: 'th-TH', // 1,000,000 (commas)
  PHP: 'en-PH', // 1,000,000 (commas)
  VND: 'vi-VN', // 1.000.000 (periods)
  INR: 'en-IN', // 10,00,000 (Indian style)
  KRW: 'ko-KR', // 1,000,000 (commas)
};

/**
 * Get currency symbol for a currency code
 */
function getCurrencySymbol(currencyCode) {
  return CURRENCY_SYMBOLS[currencyCode] || currencyCode || '';
}

/**
 * Get locale for number formatting based on currency
 */
function getCurrencyLocale(currencyCode) {
  return CURRENCY_LOCALES[currencyCode] || 'en-US';
}

/**
 * Type configuration for fallback grouping when no category is specified
 */
const TYPE_CONFIG = {
  select: { label: 'Preferences', description: 'Configure your preferences' },
  boolean: { label: 'Options', description: 'Enable or disable features' },
  integer: { label: 'Thresholds & Limits', description: 'Set thresholds and limits' },
  percentage: { label: 'Percentage Thresholds', description: 'Set percentage-based thresholds' },
  currency: { label: 'Amount Thresholds', description: 'Set currency amount thresholds' },
  text: { label: 'Configuration', description: 'Configure text-based settings' },
  textarea: { label: 'Extended Configuration', description: 'Configure extended content' },
};

/**
 * Groups settings by category (if set) or type into separate arrays for card rendering.
 * Uses category_label/description when available, otherwise falls back to TYPE_CONFIG.
 * Returns array of { type, label, description, settings[] }
 */
function groupSettingsByTypeCards(settings) {
  const typeOrder = ['select', 'boolean', 'integer', 'percentage', 'currency', 'text', 'textarea'];
  const groups = {};

  settings.forEach((setting) => {
    const groupKey = setting.category || setting.type || 'text';
    if (!groups[groupKey]) {
      groups[groupKey] = {
        settings: [],
        label: setting.category_label || TYPE_CONFIG[setting.type]?.label || groupKey,
        description: setting.category_description || TYPE_CONFIG[setting.type]?.description || '',
      };
    }
    groups[groupKey].settings.push(setting);
  });

  Object.keys(groups).forEach((key) => {
    groups[key].settings.sort((a, b) => a.label.localeCompare(b.label));
  });
  const categoryKeys = Object.keys(groups)
    .filter((k) => !typeOrder.includes(k))
    .sort();
  const typeKeys = typeOrder.filter((t) => groups[t]);

  return [...categoryKeys, ...typeKeys].map((key) => ({
    type: key,
    label: groups[key].label,
    description: groups[key].description,
    settings: groups[key].settings,
  }));
}

export default function Index({ applicationSettings = {}, modulesSettings = {} }) {
  const { url } = usePage();
  const appGroups = Object.keys(applicationSettings);
  const moduleGroups = Object.keys(modulesSettings);

  const urlParts = useMemo(() => url.split('?'), [url]);
  const queryString = urlParts[1] || '';
  const queryParams = useMemo(() => {
    return new URLSearchParams(queryString);
  }, [queryString]);

  const activeAppTab = queryParams.get('appTab') || appGroups[0] || '';
  const activeModuleTab = queryParams.get('moduleTab') || moduleGroups[0] || '';

  const defaultAppValues = useMemo(() => {
    return activeAppTab ? [activeAppTab] : appGroups.length > 0 ? [appGroups[0]] : [];
  }, [activeAppTab, appGroups]);

  const defaultModuleValues = useMemo(() => {
    return activeModuleTab ? [activeModuleTab] : moduleGroups.length > 0 ? [moduleGroups[0]] : [];
  }, [activeModuleTab, moduleGroups]);

  const initialData = useMemo(() => {
    const data = {};
    [...Object.keys(applicationSettings), ...Object.keys(modulesSettings)].forEach((group) => {
      const settings = applicationSettings[group] || modulesSettings[group] || [];
      settings.forEach((setting) => {
        data[setting.key] = setting.value ?? '';
      });
    });
    return data;
  }, [applicationSettings, modulesSettings]);

  const referenceCurrency = useMemo(() => {
    const allSettings = [
      ...Object.values(applicationSettings).flat(),
      ...Object.values(modulesSettings).flat(),
    ];
    const currencySetting = allSettings.find((s) => s.key === 'treasury_reference_currency');
    return currencySetting?.value || null;
  }, [applicationSettings, modulesSettings]);

  const form = useInertiaForm(initialData, {
    resolver: updateSettingsResolver,
    toast: {
      success: 'Settings updated successfully!',
      error: 'Failed to update settings. Please check the form for errors.',
    },
  });

  const handleAccordionChange = useCallback(
    (values, tabType) => {
      const value = Array.isArray(values) && values.length > 0 ? values[values.length - 1] : values;
      const baseUrl = urlParts[0];
      const newQueryParams = {};

      if (tabType === 'app') {
        newQueryParams.appTab = value;
        if (activeModuleTab) {
          newQueryParams.moduleTab = activeModuleTab;
        }
      } else {
        newQueryParams.moduleTab = value;
        if (activeAppTab) {
          newQueryParams.appTab = activeAppTab;
        }
      }

      router.get(baseUrl, newQueryParams, {
        preserveState: true,
        preserveScroll: true,
        only: [],
        skipLoadingIndicator: true,
      });
    },
    [urlParts, activeAppTab, activeModuleTab]
  );

  const handleAppAccordionChange = useCallback(
    (values) => handleAccordionChange(values, 'app'),
    [handleAccordionChange]
  );

  const handleModuleAccordionChange = useCallback(
    (values) => handleAccordionChange(values, 'module'),
    [handleAccordionChange]
  );

  const handleSubmit = (e) => {
    e.preventDefault();

    const queryParams = [];
    if (activeAppTab) queryParams.push(`appTab=${encodeURIComponent(activeAppTab)}`);
    if (activeModuleTab) queryParams.push(`moduleTab=${encodeURIComponent(activeModuleTab)}`);

    const updateUrl =
      queryParams.length > 0
        ? `${route('settings.update')}?${queryParams.join('&')}`
        : route('settings.update');
    form.patch(updateUrl, { silent: true });
  };

  const renderField = (setting) => {
    switch (setting.type) {
      case 'boolean':
        return (
          <Controller
            key={setting.key}
            name={setting.key}
            control={form.control}
            render={({ field }) => {
              const error = form.formState.errors[setting.key];
              return (
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <Label htmlFor={setting.key}>{setting.label}</Label>
                    <Switch
                      id={setting.key}
                      checked={field.value === true || field.value === '1' || field.value === 1}
                      onCheckedChange={field.onChange}
                    />
                  </div>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              );
            }}
          />
        );

      case 'integer':
        return (
          <FormFieldRHF
            key={setting.key}
            name={setting.key}
            control={form.control}
            label={setting.label}
            type="number"
            rules={{
              valueAsNumber: true,
            }}
          />
        );

      case 'percentage':
        return (
          <Controller
            key={setting.key}
            name={setting.key}
            control={form.control}
            render={({ field }) => {
              const error = form.formState.errors[setting.key];
              const value = Number(field.value) || 0;
              return (
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <Label htmlFor={setting.key}>{setting.label}</Label>
                    <div className="flex items-center gap-1">
                      <input
                        id={setting.key}
                        type="number"
                        min="0"
                        max="100"
                        className={cn(
                          'h-8 w-16 rounded-md border border-input bg-background px-2 py-1 text-right text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                          error && 'border-destructive'
                        )}
                        value={value}
                        onChange={(e) => {
                          const newValue = Math.min(100, Math.max(0, Number(e.target.value) || 0));
                          field.onChange(newValue);
                        }}
                      />
                      <span className="text-base font-medium text-foreground">%</span>
                    </div>
                  </div>
                  <Slider
                    value={[value]}
                    onValueChange={(values) => field.onChange(values[0])}
                    max={100}
                    min={0}
                    step={1}
                    className="w-full"
                  />
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              );
            }}
          />
        );

      case 'textarea':
        return (
          <FormTextareaRHF
            key={setting.key}
            name={setting.key}
            control={form.control}
            label={setting.label}
          />
        );

      case 'select':
        if (setting.searchable) {
          return (
            <SearchableSelectRHF
              key={setting.key}
              name={setting.key}
              control={form.control}
              label={setting.label}
              options={setting.options || []}
              placeholder="Search..."
            />
          );
        }

        return (
          <Controller
            key={setting.key}
            name={setting.key}
            control={form.control}
            render={({ field }) => {
              const error = form.formState.errors[setting.key];
              return (
                <div className="space-y-2">
                  <Label htmlFor={setting.key}>{setting.label}</Label>
                  <Select value={field.value || ''} onValueChange={field.onChange}>
                    <SelectTrigger
                      id={setting.key}
                      className={cn('w-full', error && 'border-destructive')}
                    >
                      <SelectValue placeholder="Select an option" />
                    </SelectTrigger>
                    <SelectContent>
                      {setting.options?.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              );
            }}
          />
        );

      case 'currency':
        return (
          <Controller
            key={setting.key}
            name={setting.key}
            control={form.control}
            render={({ field }) => {
              const error = form.formState.errors[setting.key];
              const currencySymbol = getCurrencySymbol(referenceCurrency);
              const locale = getCurrencyLocale(referenceCurrency);

              const formatNumber = (num) => {
                if (num === null || num === undefined || num === '') return '';
                return Number(num).toLocaleString(locale);
              };

              const parseNumber = (str) => {
                if (!str) return 0;
                return Number(str.replace(/[.,]/g, '')) || 0;
              };

              return (
                <div className="space-y-2">
                  <Label htmlFor={setting.key}>{setting.label}</Label>
                  <div className="relative">
                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">
                      {currencySymbol}
                    </span>
                    <input
                      id={setting.key}
                      type="text"
                      inputMode="numeric"
                      className={cn(
                        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                        'pl-12',
                        error && 'border-destructive'
                      )}
                      value={formatNumber(field.value)}
                      onChange={(e) => {
                        const value = e.target.value.replace(/[^0-9.,]/g, '');
                        field.onChange(parseNumber(value));
                      }}
                      onBlur={() => {
                        field.onBlur();
                      }}
                    />
                  </div>
                  {error && <p className="text-sm text-destructive">{error.message}</p>}
                </div>
              );
            }}
          />
        );

      case 'text':
      default:
        return (
          <FormFieldRHF
            key={setting.key}
            name={setting.key}
            control={form.control}
            label={setting.label}
            type="text"
            placeholder={setting.key === 'app_logo' ? 'e.g., Cog, Home, Settings, User' : undefined}
          />
        );
    }
  };

  const hasSettings = appGroups.length > 0 || moduleGroups.length > 0;

  return (
    <PageShell title="Application Settings">
      {!hasSettings ? (
        <div className="rounded-lg border border-dashed p-12 text-center">
          <p className="text-muted-foreground">No settings available at this time.</p>
        </div>
      ) : (
        <form id="settings-form" onSubmit={handleSubmit} className="space-y-8" noValidate>
          <div className="grid gap-6 md:grid-cols-2 md:items-start">
            {appGroups.length > 0 && (
              <Card className="flex flex-col">
                <CardHeader className="pb-2">
                  <CardTitle className="text-2xl">Application Settings</CardTitle>
                  <p className="text-sm text-muted-foreground mt-1">
                    Core application and system settings
                  </p>
                </CardHeader>
                <CardContent className="flex-1">
                  <Accordion
                    type="multiple"
                    defaultValue={defaultAppValues}
                    onValueChange={handleAppAccordionChange}
                    className="w-full"
                  >
                    {appGroups.map((group) => {
                      const groupSettings = applicationSettings[group] || [];

                      return (
                        <AccordionItem key={group} value={group}>
                          <AccordionTrigger className="capitalize">{group}</AccordionTrigger>
                          <AccordionContent className="space-y-6">
                            {groupSettingsByTypeCards(groupSettings).map((typeGroup) => (
                              <FormCard
                                key={typeGroup.type}
                                title={typeGroup.label}
                                description={typeGroup.description}
                                className="max-w-4xl"
                              >
                                <div className="space-y-6">
                                  {typeGroup.settings.map((setting) => (
                                    <div key={setting.key}>{renderField(setting)}</div>
                                  ))}
                                </div>
                              </FormCard>
                            ))}
                          </AccordionContent>
                        </AccordionItem>
                      );
                    })}
                  </Accordion>
                </CardContent>
              </Card>
            )}

            {moduleGroups.length > 0 && (
              <Card className="flex flex-col">
                <CardHeader className="pb-2">
                  <CardTitle className="text-2xl">Modules Settings</CardTitle>
                  <p className="text-sm text-muted-foreground mt-1">
                    Settings from installed modules
                  </p>
                </CardHeader>
                <CardContent className="flex-1">
                  <Accordion
                    type="multiple"
                    defaultValue={defaultModuleValues}
                    onValueChange={handleModuleAccordionChange}
                    className="w-full"
                  >
                    {moduleGroups.map((group) => {
                      const groupSettings = modulesSettings[group] || [];

                      return (
                        <AccordionItem key={group} value={group}>
                          <AccordionTrigger className="capitalize">{group}</AccordionTrigger>
                          <AccordionContent className="space-y-6">
                            {groupSettingsByTypeCards(groupSettings).map((typeGroup) => (
                              <FormCard
                                key={typeGroup.type}
                                title={typeGroup.label}
                                description={typeGroup.description}
                                className="max-w-4xl"
                              >
                                <div className="space-y-6">
                                  {typeGroup.settings.map((setting) => (
                                    <div key={setting.key}>{renderField(setting)}</div>
                                  ))}
                                </div>
                              </FormCard>
                            ))}
                          </AccordionContent>
                        </AccordionItem>
                      );
                    })}
                  </Accordion>
                </CardContent>
              </Card>
            )}
          </div>

          <div className="flex items-center gap-4">
            <Button type="submit" disabled={form.formState.isSubmitting}>
              {form.formState.isSubmitting ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      )}
    </PageShell>
  );
}
