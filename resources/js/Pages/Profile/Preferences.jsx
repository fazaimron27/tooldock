import { router } from '@inertiajs/react';
import { Bell, Monitor, Palette, RotateCcw, Shield, Volume2 } from 'lucide-react';
import { useState } from 'react';

import FormCard from '@/Components/Common/FormCard';
import PageShell from '@/Components/Layouts/PageShell';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Button } from '@/Components/ui/button';
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
 * User Preferences page component
 *
 * Displays user-overridable settings grouped by category.
 * Users can customize their notification and security preferences.
 */
export default function Preferences({ grouped }) {
  const [processing, setProcessing] = useState(false);
  const [resetKey, setResetKey] = useState(null);

  const handleToggle = (key, currentValue) => {
    setProcessing(true);
    router.post(
      route('preferences.update'),
      { key, value: !currentValue },
      {
        preserveScroll: true,
        onFinish: () => setProcessing(false),
      }
    );
  };

  const handleSelect = (key, value) => {
    setProcessing(true);
    router.post(
      route('preferences.update'),
      { key, value },
      {
        preserveScroll: true,
        onFinish: () => setProcessing(false),
      }
    );
  };

  const handleSlider = (key, value) => {
    setProcessing(true);
    router.post(
      route('preferences.update'),
      { key, value: Math.round(value * 100) },
      {
        preserveScroll: true,
        onFinish: () => setProcessing(false),
      }
    );
  };

  const handleReset = (key) => {
    setResetKey(key);
    router.post(
      route('preferences.reset'),
      { key },
      {
        preserveScroll: true,
        onFinish: () => setResetKey(null),
      }
    );
  };

  const handleResetAll = () => {
    setProcessing(true);
    router.post(
      route('preferences.resetAll'),
      {},
      {
        preserveScroll: true,
        onFinish: () => setProcessing(false),
      }
    );
  };

  const getCategoryIcon = (category) => {
    if (category.includes('ui_preferences')) return Palette;
    if (category.includes('notification_preferences')) return Volume2;
    if (category.includes('notifications')) return Bell;
    if (category.includes('security')) return Shield;
    return Monitor;
  };

  const categories = Object.entries(grouped || {});

  /**
   * Render appropriate input based on setting type
   */
  const renderSettingInput = (setting) => {
    const { key, type, value, options } = setting;

    switch (type) {
      case 'boolean':
        return (
          <Switch
            id={key}
            checked={value === true || value === 'true'}
            onCheckedChange={() => handleToggle(key, value)}
            disabled={processing}
          />
        );

      case 'select':
        return (
          <Select
            value={String(value)}
            onValueChange={(newValue) => handleSelect(key, newValue)}
            disabled={processing}
          >
            <SelectTrigger className="w-[140px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {options &&
                Object.entries(options).map(([optionValue, optionLabel]) => (
                  <SelectItem key={optionValue} value={optionValue}>
                    {optionLabel}
                  </SelectItem>
                ))}
            </SelectContent>
          </Select>
        );

      case 'percentage': {
        // Convert stored percentage (0-100) to slider value (0-1)
        const sliderValue = typeof value === 'number' ? value : parseInt(value, 10) || 50;
        return (
          <div className="flex items-center gap-3 w-[180px]">
            <Slider
              value={[sliderValue / 100]}
              min={0}
              max={1}
              step={0.1}
              onValueCommit={([v]) => handleSlider(key, v)}
              disabled={processing}
              className="flex-1"
            />
            <span className="text-sm text-muted-foreground w-10 text-right">{sliderValue}%</span>
          </div>
        );
      }

      default:
        return <span className="text-sm text-muted-foreground">{String(value)}</span>;
    }
  };

  return (
    <PageShell title="Preferences">
      <div className="space-y-6">
        {/* Header with reset all button */}
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-lg font-medium">My Preferences</h2>
            <p className="text-sm text-muted-foreground">
              Customize your notification and security settings. These override the default
              settings.
            </p>
          </div>
          <AlertDialog>
            <AlertDialogTrigger asChild>
              <Button variant="outline" size="sm" disabled={processing}>
                <RotateCcw className="mr-2 h-4 w-4" />
                Reset All
              </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Reset All Preferences</AlertDialogTitle>
                <AlertDialogDescription>
                  This will reset all your preferences to their default values. This action cannot
                  be undone.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction onClick={handleResetAll}>Reset All</AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>

        {/* Preference categories */}
        {categories.length === 0 ? (
          <FormCard
            title="No Preferences Available"
            description="There are no user-customizable preferences at this time."
          >
            <p className="text-sm text-muted-foreground">
              Check back later or contact your administrator.
            </p>
          </FormCard>
        ) : (
          categories.map(([categoryKey, category]) => (
            <FormCard
              key={categoryKey}
              title={category.label}
              description={category.description}
              icon={getCategoryIcon(categoryKey)}
            >
              <div className="space-y-4">
                {category.settings?.map((setting) => (
                  <div
                    key={setting.key}
                    className="flex items-center justify-between py-2 border-b last:border-0"
                  >
                    <div className="flex-1">
                      <Label htmlFor={setting.key} className="font-medium">
                        {setting.label}
                      </Label>
                      {setting.hasOverride && (
                        <span className="ml-2 text-xs text-primary bg-primary/10 px-2 py-0.5 rounded">
                          Custom
                        </span>
                      )}
                    </div>
                    <div className="flex items-center gap-2">
                      {setting.hasOverride && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleReset(setting.key)}
                          disabled={resetKey === setting.key}
                          className="text-muted-foreground hover:text-foreground"
                        >
                          <RotateCcw className="h-3.5 w-3.5" />
                        </Button>
                      )}
                      {renderSettingInput(setting)}
                    </div>
                  </div>
                ))}
              </div>
            </FormCard>
          ))
        )}
      </div>
    </PageShell>
  );
}
