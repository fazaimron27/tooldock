/**
 * Folio Builder Page
 *
 * Three-pane layout: form sections (left), live preview (center),
 * template & settings (right).
 * Uses react-hook-form for state management and auto-save.
 */
import BuilderFormPane from '@Folio/Components/BuilderFormPane';
import ResumePreview from '@Folio/Components/ResumePreview';
import ResumeSettings from '@Folio/Components/ResumeSettings';
import TemplateSelector from '@Folio/Components/TemplateSelector';
import useAutoSave from '@Folio/Hooks/useAutoSave';
import useBuilderLayout from '@Folio/Hooks/useBuilderLayout';
import useFolioUpdate from '@Folio/Hooks/useFolioUpdate';
import { DEFAULT_CONTENT, STATUS_MAP, mergeDefaults } from '@Folio/constants/defaultContent';
import { usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useFieldArray, useForm } from 'react-hook-form';

import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

export default function Builder() {
  const { folio, content } = usePage().props;
  const { builderRef, paneHeight } = useBuilderLayout();

  const {
    control,
    watch,
    setValue,
    formState: { isDirty },
  } = useForm({
    defaultValues: mergeDefaults(DEFAULT_CONTENT, content),
  });

  const { saveStatus } = useAutoSave(folio.id, watch, isDirty);
  const folioUpdate = useFolioUpdate(folio.id);
  const liveContent = watch();
  const themeId = liveContent.template || 'professional';

  const {
    fields: customSections,
    append: appendCustom,
    remove: removeCustom,
  } = useFieldArray({
    control,
    name: 'sections.custom',
  });

  const setThemeId = (id) => {
    setValue('template', id, { shouldDirty: true });
    const current = watch();
    folioUpdate.mutate({ content: { ...current, template: id } });
  };

  const statusInfo = STATUS_MAP[saveStatus] || STATUS_MAP.idle;
  const StatusIcon = statusInfo.icon;

  return (
    <PageShell
      title={folio.name}
      description="Edit your resume"
      actions={
        <div className="flex items-center gap-3">
          <Badge variant={statusInfo.variant} className="gap-1.5">
            {StatusIcon && <StatusIcon className="h-3 w-3" />}
            {statusInfo.label}
          </Badge>
          <Button
            variant="outline"
            onClick={() => {
              window.open(route('folio.print', folio.id), '_blank');
            }}
          >
            <Download className="mr-2 h-4 w-4" />
            Print / Save PDF
          </Button>
        </div>
      }
    >
      <div
        ref={builderRef}
        className="flex gap-0 flex-1 overflow-hidden"
        style={{ height: paneHeight ? `${paneHeight}px` : 'auto' }}
      >
        <BuilderFormPane
          control={control}
          customSections={customSections}
          appendCustom={appendCustom}
          removeCustom={removeCustom}
        />

        <div className="flex-1 overflow-y-auto bg-muted/30 px-6 py-4">
          <ResumePreview content={liveContent} themeId={themeId} />
        </div>

        <div className="w-[380px] shrink-0 overflow-y-auto border-l pl-4">
          <div className="space-y-6">
            <div>
              <h3 className="text-sm font-semibold mb-3 flex items-center gap-2">
                <svg
                  className="h-4 w-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth={2}
                >
                  <rect x="3" y="3" width="7" height="7" rx="1" />
                  <rect x="14" y="3" width="7" height="7" rx="1" />
                  <rect x="3" y="14" width="7" height="7" rx="1" />
                  <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
                Template
              </h3>
              <TemplateSelector value={themeId} onChange={setThemeId} />
            </div>
            <ResumeSettings control={control} setValue={setValue} />
          </div>
        </div>
      </div>
    </PageShell>
  );
}
