/**
 * Settings management page with grouped tabs and dynamic form fields
 * Allows admins to manage application settings organized by groups
 * Uses URL query parameters for tabs to ensure proper flash message handling
 */
import { useSmartForm } from '@/Hooks/useSmartForm';
import { router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

import FormCard from '@/Components/Common/FormCard';
import FormField from '@/Components/Common/FormField';
import FormTextarea from '@/Components/Common/FormTextarea';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ applicationSettings = {}, modulesSettings = {} }) {
  const { url } = usePage();
  const appGroups = Object.keys(applicationSettings);
  const moduleGroups = Object.keys(modulesSettings);

  const urlParts = url.split('?');
  const queryString = urlParts[1] || '';
  const getQueryParam = (param) => {
    const params = queryString.split('&');
    for (const p of params) {
      const [key, value] = p.split('=');
      if (key === param) {
        return decodeURIComponent(value || '');
      }
    }
    return null;
  };

  const activeAppTab = getQueryParam('appTab') || appGroups[0] || '';
  const activeModuleTab = getQueryParam('moduleTab') || moduleGroups[0] || '';

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

  const form = useSmartForm(initialData, {
    toast: {
      success: 'Settings updated successfully!',
      error: 'Failed to update settings. Please check the form for errors.',
    },
  });

  const handleAppTabChange = (value) => {
    const baseUrl = urlParts[0];
    const queryParams = { appTab: value };
    if (activeModuleTab) {
      queryParams.moduleTab = activeModuleTab;
    }
    router.get(baseUrl, queryParams, {
      preserveState: true,
      preserveScroll: true,
      only: [],
      skipLoadingIndicator: true,
    });
  };

  const handleModuleTabChange = (value) => {
    const baseUrl = urlParts[0];
    const queryParams = { moduleTab: value };
    if (activeAppTab) {
      queryParams.appTab = activeAppTab;
    }
    router.get(baseUrl, queryParams, {
      preserveState: true,
      preserveScroll: true,
      only: [],
      skipLoadingIndicator: true,
    });
  };

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
    const value = form.data[setting.key];
    const error = form.errors[setting.key];

    switch (setting.type) {
      case 'boolean':
        return (
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label htmlFor={setting.key}>{setting.label}</Label>
              <Switch
                id={setting.key}
                checked={value === true || value === '1' || value === 1}
                onCheckedChange={(checked) => form.setData(setting.key, checked)}
              />
            </div>
            {error && <p className="text-sm text-destructive">{error}</p>}
          </div>
        );

      case 'integer':
        return (
          <FormField
            name={setting.key}
            label={setting.label}
            type="number"
            value={value}
            onChange={(e) => form.setData(setting.key, parseInt(e.target.value, 10) || 0)}
            error={error}
          />
        );

      case 'textarea':
        return (
          <FormTextarea
            name={setting.key}
            label={setting.label}
            value={value}
            onChange={(e) => form.setData(setting.key, e.target.value)}
            error={error}
          />
        );

      case 'text':
      default:
        return (
          <FormField
            name={setting.key}
            label={setting.label}
            type="text"
            value={value}
            onChange={(e) => form.setData(setting.key, e.target.value)}
            error={error}
            placeholder={setting.key === 'app_logo' ? 'e.g., Cog, Home, Settings, User' : undefined}
          />
        );
    }
  };

  const getGridColsClass = (count) => {
    const gridColsMap = {
      1: 'grid-cols-1',
      2: 'grid-cols-2',
      3: 'grid-cols-3',
      4: 'grid-cols-4',
      5: 'grid-cols-5',
      6: 'grid-cols-6',
      7: 'grid-cols-7',
      8: 'grid-cols-8',
      9: 'grid-cols-9',
      10: 'grid-cols-10',
      11: 'grid-cols-11',
      12: 'grid-cols-12',
    };
    if (count > 12) {
      return 'grid-cols-6';
    }
    return gridColsMap[count] || 'grid-cols-5';
  };

  return (
    <DashboardLayout header="Settings">
      <PageShell title="Application Settings">
        <form onSubmit={handleSubmit} className="space-y-8" noValidate>
          {appGroups.length > 0 && (
            <div className="space-y-6">
              <h2 className="text-2xl font-semibold">Application Settings</h2>
              <Tabs value={activeAppTab} onValueChange={handleAppTabChange} className="w-full">
                <div className={appGroups.length > 12 ? 'overflow-x-auto' : ''}>
                  <TabsList className={`grid w-full ${getGridColsClass(appGroups.length)}`}>
                    {appGroups.map((group) => (
                      <TabsTrigger key={group} value={group} className="capitalize">
                        {group}
                      </TabsTrigger>
                    ))}
                  </TabsList>
                </div>

                {appGroups.map((group) => {
                  const groupSettings = applicationSettings[group] || [];
                  const modules = [...new Set(groupSettings.map((s) => s.module).filter(Boolean))];
                  const moduleLabel = modules.length === 1 ? modules[0] : modules.join(', ');

                  return (
                    <TabsContent key={group} value={group} className="space-y-6">
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
                    </TabsContent>
                  );
                })}
              </Tabs>
            </div>
          )}

          {appGroups.length > 0 && moduleGroups.length > 0 && <div className="border-t pt-8" />}

          {moduleGroups.length > 0 && (
            <div className="space-y-6">
              <h2 className="text-2xl font-semibold">Modules Settings</h2>
              <Tabs
                value={activeModuleTab}
                onValueChange={handleModuleTabChange}
                className="w-full"
              >
                <div className={moduleGroups.length > 12 ? 'overflow-x-auto' : ''}>
                  <TabsList className={`grid w-full ${getGridColsClass(moduleGroups.length)}`}>
                    {moduleGroups.map((group) => (
                      <TabsTrigger key={group} value={group} className="capitalize">
                        {group}
                      </TabsTrigger>
                    ))}
                  </TabsList>
                </div>

                {moduleGroups.map((group) => {
                  const groupSettings = modulesSettings[group] || [];
                  const modules = [...new Set(groupSettings.map((s) => s.module).filter(Boolean))];
                  const moduleLabel = modules.length === 1 ? modules[0] : modules.join(', ');

                  return (
                    <TabsContent key={group} value={group} className="space-y-6">
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
                    </TabsContent>
                  );
                })}
              </Tabs>
            </div>
          )}

          <div className="flex items-center gap-4">
            <Button type="submit" disabled={form.processing}>
              {form.processing ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      </PageShell>
    </DashboardLayout>
  );
}
