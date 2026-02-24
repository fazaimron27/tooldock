/**
 * Resume Settings Panel
 *
 * Customization panels for Layout, Typography, Design, and Page settings.
 * Appears in the right sidebar below the template selector.
 */
import { COLOR_PRESETS } from '@Folio/constants/colorPresets';
import { FONT_OPTIONS } from '@Folio/constants/fonts';
import { FileText, LayoutGrid, Paintbrush, Type } from 'lucide-react';
import { Controller } from 'react-hook-form';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

import LayoutPanel from './Settings/LayoutPanel';
import NumberField from './Settings/NumberField';
import SettingsSection from './Settings/SettingsSection';

export default function ResumeSettings({ control, setValue }) {
  return (
    <div className="space-y-3">
      <SettingsSection icon={LayoutGrid} title="Layout">
        <LayoutPanel control={control} setValue={setValue} />
      </SettingsSection>

      <SettingsSection icon={Type} title="Typography">
        <fieldset className="space-y-3">
          <legend className="text-xs font-semibold text-muted-foreground border-b pb-1 w-full mb-2">
            Body
          </legend>
          <div>
            <Label className="text-xs">Font Family</Label>
            <Controller
              name="settings.typography.body.fontFamily"
              control={control}
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger className="mt-1">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {FONT_OPTIONS.map((f) => (
                      <SelectItem key={f.value} value={f.value}>
                        {f.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>
          <div className="grid grid-cols-2 gap-3 items-end">
            <NumberField
              label="Font Size"
              name="settings.typography.body.fontSize"
              control={control}
              step={0.5}
              min={8}
              max={16}
              unit="pt"
            />
            <NumberField
              label="Line Height"
              name="settings.typography.body.lineHeight"
              control={control}
              step={0.1}
              min={1}
              max={3}
              unit="×"
            />
          </div>
        </fieldset>

        <fieldset className="space-y-3">
          <legend className="text-xs font-semibold text-muted-foreground border-b pb-1 w-full mb-2">
            Heading
          </legend>
          <div>
            <Label className="text-xs">Font Family</Label>
            <Controller
              name="settings.typography.heading.fontFamily"
              control={control}
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger className="mt-1">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {FONT_OPTIONS.map((f) => (
                      <SelectItem key={f.value} value={f.value}>
                        {f.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>
          <div className="grid grid-cols-2 gap-3 items-end">
            <NumberField
              label="Font Size"
              name="settings.typography.heading.fontSize"
              control={control}
              step={0.5}
              min={10}
              max={24}
              unit="pt"
            />
            <NumberField
              label="Line Height"
              name="settings.typography.heading.lineHeight"
              control={control}
              step={0.1}
              min={1}
              max={3}
              unit="×"
            />
          </div>
        </fieldset>
      </SettingsSection>

      <SettingsSection icon={Paintbrush} title="Design">
        <div>
          <div className="flex flex-wrap gap-1.5">
            <Controller
              name="settings.design.primaryColor"
              control={control}
              render={({ field }) => (
                <>
                  {COLOR_PRESETS.map((c) => (
                    <button
                      key={c}
                      type="button"
                      onClick={() => field.onChange(c)}
                      className={`h-5 w-5 rounded-sm border transition-all ${field.value === c ? 'ring-2 ring-primary ring-offset-1' : 'border-transparent hover:scale-110'}`}
                      style={{ backgroundColor: c }}
                    />
                  ))}
                </>
              )}
            />
          </div>
        </div>

        <div>
          <Label className="text-xs">Primary Color</Label>
          <div className="flex items-center gap-2 mt-1">
            <Controller
              name="settings.design.primaryColor"
              control={control}
              render={({ field }) => (
                <>
                  <input
                    type="color"
                    value={field.value}
                    onChange={(e) => field.onChange(e.target.value)}
                    className="h-8 w-8 rounded border cursor-pointer"
                  />
                  <Input
                    value={field.value}
                    onChange={(e) => field.onChange(e.target.value)}
                    className="text-sm flex-1"
                  />
                </>
              )}
            />
          </div>
        </div>

        <div>
          <Label className="text-xs">Text Color</Label>
          <div className="flex items-center gap-2 mt-1">
            <Controller
              name="settings.design.textColor"
              control={control}
              render={({ field }) => (
                <>
                  <input
                    type="color"
                    value={field.value}
                    onChange={(e) => field.onChange(e.target.value)}
                    className="h-8 w-8 rounded border cursor-pointer"
                  />
                  <Input
                    value={field.value}
                    onChange={(e) => field.onChange(e.target.value)}
                    className="text-sm flex-1"
                  />
                </>
              )}
            />
          </div>
        </div>

        <div>
          <Label className="text-xs">Background Color</Label>
          <div className="flex items-center gap-2 mt-1">
            <Controller
              name="settings.design.backgroundColor"
              control={control}
              render={({ field }) => (
                <>
                  <input
                    type="color"
                    value={field.value}
                    onChange={(e) => field.onChange(e.target.value)}
                    className="h-8 w-8 rounded border cursor-pointer"
                  />
                  <Input
                    value={field.value}
                    onChange={(e) => field.onChange(e.target.value)}
                    className="text-sm flex-1"
                  />
                </>
              )}
            />
          </div>
        </div>
      </SettingsSection>

      <SettingsSection icon={FileText} title="Page">
        <div>
          <Label className="text-xs">Format</Label>
          <Controller
            name="settings.page.format"
            control={control}
            render={({ field }) => (
              <Select value={field.value} onValueChange={field.onChange}>
                <SelectTrigger className="mt-1">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="a4">A4</SelectItem>
                  <SelectItem value="letter">Letter</SelectItem>
                </SelectContent>
              </Select>
            )}
          />
        </div>

        <div className="grid grid-cols-2 gap-3 items-end">
          <NumberField
            label="Margin (H)"
            name="settings.page.marginHorizontal"
            control={control}
            min={0}
            max={72}
            unit="pt"
          />
          <NumberField
            label="Margin (V)"
            name="settings.page.marginVertical"
            control={control}
            min={0}
            max={72}
            unit="pt"
          />
        </div>

        <div className="grid grid-cols-2 gap-3 items-end">
          <NumberField
            label="Spacing (H)"
            name="settings.page.spacingHorizontal"
            control={control}
            min={0}
            max={36}
            unit="pt"
          />
          <NumberField
            label="Spacing (V)"
            name="settings.page.spacingVertical"
            control={control}
            min={0}
            max={36}
            unit="pt"
          />
        </div>
      </SettingsSection>
    </div>
  );
}
