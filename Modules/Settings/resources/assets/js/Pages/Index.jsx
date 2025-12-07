/**
 * Settings management page with grouped accordion sections and dynamic form fields
 * Allows admins to manage application settings organized by groups
 * Uses URL query parameters for accordion sections to ensure proper flash message handling
 * Uses React Hook Form for improved performance and validation
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import { updateSettingsResolver } from '@Settings/Schemas/settingsSchemas';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { Controller } from 'react-hook-form';

import FormCard from '@/Components/Common/FormCard';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import FormTextarea from '@/Components/Common/FormTextarea';
import PageShell from '@/Components/Layouts/PageShell';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/Components/ui/accordion';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

import DashboardLayout from '@/Layouts/DashboardLayout';

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

      case 'textarea':
        return (
          <Controller
            key={setting.key}
            name={setting.key}
            control={form.control}
            render={({ field, fieldState: { error: fieldError } }) => (
              <div className="space-y-2">
                <Label htmlFor={setting.key}>{setting.label}</Label>
                <FormTextarea
                  name={setting.key}
                  label={setting.label}
                  value={field.value || ''}
                  onChange={field.onChange}
                  onBlur={field.onBlur}
                  error={fieldError?.message}
                />
              </div>
            )}
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
    <DashboardLayout header="Settings">
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
                  <CardHeader>
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
                        const modules = [
                          ...new Set(groupSettings.map((s) => s.module).filter(Boolean)),
                        ];
                        const moduleLabel = modules.length === 1 ? modules[0] : modules.join(', ');

                        return (
                          <AccordionItem key={group} value={group}>
                            <AccordionTrigger className="capitalize">
                              <div className="flex items-center gap-2">
                                <span>{group}</span>
                                {moduleLabel && (
                                  <Badge variant="outline" className="text-xs font-normal">
                                    {moduleLabel}
                                  </Badge>
                                )}
                              </div>
                            </AccordionTrigger>
                            <AccordionContent className="space-y-6">
                              <FormCard
                                title={`${group.charAt(0).toUpperCase() + group.slice(1)} Settings`}
                                description={
                                  moduleLabel
                                    ? `Manage ${group} related settings from ${moduleLabel} module${modules.length > 1 ? 's' : ''}`
                                    : `Manage ${group} related settings`
                                }
                                className="max-w-4xl"
                              >
                                <div className="space-y-6">
                                  {groupSettings.map((setting) => (
                                    <div key={setting.key}>{renderField(setting)}</div>
                                  ))}
                                </div>
                              </FormCard>
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
                  <CardHeader>
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
                        const modules = [
                          ...new Set(groupSettings.map((s) => s.module).filter(Boolean)),
                        ];
                        const moduleLabel = modules.length === 1 ? modules[0] : modules.join(', ');

                        return (
                          <AccordionItem key={group} value={group}>
                            <AccordionTrigger className="capitalize">
                              <div className="flex items-center gap-2">
                                <span>{group}</span>
                                {moduleLabel && (
                                  <Badge variant="outline" className="text-xs font-normal">
                                    {moduleLabel}
                                  </Badge>
                                )}
                              </div>
                            </AccordionTrigger>
                            <AccordionContent className="space-y-6">
                              <FormCard
                                title={`${group.charAt(0).toUpperCase() + group.slice(1)} Settings`}
                                description={
                                  moduleLabel
                                    ? `Manage ${group} related settings from ${moduleLabel} module${modules.length > 1 ? 's' : ''}`
                                    : `Manage ${group} related settings`
                                }
                                className="max-w-4xl"
                              >
                                <div className="space-y-6">
                                  {groupSettings.map((setting) => (
                                    <div key={setting.key}>{renderField(setting)}</div>
                                  ))}
                                </div>
                              </FormCard>
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
    </DashboardLayout>
  );
}
