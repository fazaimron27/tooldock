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

export default function Index({ settings = {} }) {
  const { url } = usePage();
  const groups = Object.keys(settings);

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
  const activeTab = getQueryParam('tab') || groups[0] || '';

  const initialData = useMemo(() => {
    const data = {};
    Object.keys(settings).forEach((group) => {
      settings[group].forEach((setting) => {
        data[setting.key] = setting.value ?? '';
      });
    });
    return data;
  }, [settings]);

  const form = useSmartForm(initialData, {
    toast: {
      success: 'Settings updated successfully!',
      error: 'Failed to update settings. Please check the form for errors.',
    },
  });

  const handleTabChange = (value) => {
    const baseUrl = urlParts[0];
    router.get(
      baseUrl,
      { tab: value },
      {
        preserveState: true,
        preserveScroll: true,
        only: [],
      }
    );
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    const updateUrl = activeTab
      ? `${route('settings.update')}?tab=${encodeURIComponent(activeTab)}`
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
          />
        );
    }
  };

  return (
    <DashboardLayout header="Settings">
      <PageShell title="Application Settings">
        <form onSubmit={handleSubmit} className="space-y-6" noValidate>
          <Tabs value={activeTab} onValueChange={handleTabChange} className="w-full">
            <TabsList
              className={`grid w-full ${
                groups.length === 1
                  ? 'grid-cols-1'
                  : groups.length === 2
                    ? 'grid-cols-2'
                    : groups.length === 3
                      ? 'grid-cols-3'
                      : groups.length === 4
                        ? 'grid-cols-4'
                        : 'grid-cols-5'
              }`}
            >
              {groups.map((group) => (
                <TabsTrigger key={group} value={group} className="capitalize">
                  {group}
                </TabsTrigger>
              ))}
            </TabsList>

            {groups.map((group) => {
              const modules = [...new Set(settings[group]?.map((s) => s.module).filter(Boolean))];
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
                      {settings[group]?.map((setting) => (
                        <div key={setting.key}>{renderField(setting)}</div>
                      ))}
                    </div>
                  </FormCard>
                </TabsContent>
              );
            })}
          </Tabs>

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
