/**
 * NucleusToolbar
 *
 * Top action bar for the Nucleus JSON editor. Provides Prettify, Minify,
 * Clear, Copy to Clipboard, and Save Snippet controls.
 *
 * A second "Transform" row contains one-click operators that mutate the
 * editor content: Sort Keys, Remove Nulls, Flatten, and Extract Schema.
 *
 * The Save dialog follows the established FormDialog + FormFieldRHF pattern
 * used across QuickDraw, Vault, and other modules.
 */
import {
  ArrowDownAz,
  Braces,
  Check,
  Clipboard,
  Eraser,
  Layers,
  Maximize2,
  Minus,
  Save,
  Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';

import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

const SAVE_FORM_ID = 'nucleus-save-snippet-form';

const TRANSFORMS = [
  {
    key: 'sortKeys',
    icon: ArrowDownAz,
    label: 'Sort Keys',
    tip: 'Recursively sort all object keys alphabetically',
  },
  {
    key: 'removeNulls',
    icon: Trash2,
    label: 'Remove Nulls',
    tip: 'Strip all null and undefined values from the JSON',
  },
  {
    key: 'flatten',
    icon: Layers,
    label: 'Flatten',
    tip: 'Collapse nested objects into dot-notation keys',
  },
  {
    key: 'schema',
    icon: Braces,
    label: 'Schema',
    tip: 'Replace every value with its type (string, number, …)',
  },
];

export default function NucleusToolbar({
  onPrettify,
  onMinify,
  onClear,
  onSave,
  onTransform,
  isValidJson,
  isSaving,
}) {
  const [copied, setCopied] = useState(false);
  const [saveOpen, setSaveOpen] = useState(false);

  const { control, handleSubmit, reset } = useForm({
    defaultValues: { title: '' },
  });

  const handleCopy = async () => {
    const text = onPrettify(true);
    if (text) {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  const openSave = () => {
    reset({ title: '' });
    setSaveOpen(true);
  };

  const onSubmit = handleSubmit(({ title }) => {
    onSave(title.trim());
    setSaveOpen(false);
    reset({ title: '' });
  });

  return (
    <>
      {/* ── Primary row ── */}
      <div className="flex items-center justify-between border-b border-border bg-card px-4 py-2">
        <div className="flex items-center gap-1.5">
          <TooltipProvider delayDuration={300}>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onPrettify()}
                  disabled={!isValidJson}
                  className="h-8 gap-1.5 px-3 text-xs font-medium text-muted-foreground hover:text-foreground disabled:opacity-40"
                >
                  <Maximize2 className="h-3.5 w-3.5" />
                  Prettify
                </Button>
              </TooltipTrigger>
              <TooltipContent>Format JSON with indentation</TooltipContent>
            </Tooltip>

            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={onMinify}
                  disabled={!isValidJson}
                  className="h-8 gap-1.5 px-3 text-xs font-medium text-muted-foreground hover:text-foreground disabled:opacity-40"
                >
                  <Minus className="h-3.5 w-3.5" />
                  Minify
                </Button>
              </TooltipTrigger>
              <TooltipContent>Collapse JSON to single line</TooltipContent>
            </Tooltip>

            <div className="mx-1 h-5 w-px bg-border" />

            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={handleCopy}
                  disabled={!isValidJson}
                  className="h-8 gap-1.5 px-3 text-xs font-medium text-muted-foreground hover:text-foreground disabled:opacity-40"
                >
                  {copied ? (
                    <Check className="h-3.5 w-3.5 text-emerald-500" />
                  ) : (
                    <Clipboard className="h-3.5 w-3.5" />
                  )}
                  {copied ? 'Copied!' : 'Copy'}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Copy formatted JSON to clipboard</TooltipContent>
            </Tooltip>

            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={onClear}
                  className="h-8 gap-1.5 px-3 text-xs font-medium text-muted-foreground hover:text-destructive"
                >
                  <Eraser className="h-3.5 w-3.5" />
                  Clear
                </Button>
              </TooltipTrigger>
              <TooltipContent>Clear editor content</TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>

        <Button
          size="sm"
          onClick={openSave}
          disabled={!isValidJson || isSaving}
          className="h-8 gap-1.5 px-3 text-xs"
        >
          <Save className="h-3.5 w-3.5" />
          {isSaving ? 'Saving…' : 'Save Snippet'}
        </Button>
      </div>

      {/* ── Transform row ── */}
      <div className="flex items-center gap-1 border-b border-border bg-muted/10 px-4 py-1 overflow-x-auto">
        <span className="mr-1 shrink-0 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground/50">
          Transform
        </span>
        <TooltipProvider delayDuration={300}>
          {TRANSFORMS.map(({ key, icon: Icon, label, tip }) => (
            <Tooltip key={key}>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onTransform(key)}
                  disabled={!isValidJson}
                  className="h-7 gap-1.5 px-2.5 text-[11px] font-medium text-muted-foreground hover:text-foreground disabled:opacity-40"
                >
                  <Icon className="h-3 w-3" />
                  {label}
                </Button>
              </TooltipTrigger>
              <TooltipContent>{tip}</TooltipContent>
            </Tooltip>
          ))}
        </TooltipProvider>
      </div>

      {/* Save Snippet — uses the shared FormDialog + FormFieldRHF pattern */}
      <FormDialog
        open={saveOpen}
        onOpenChange={setSaveOpen}
        onCancel={() => setSaveOpen(false)}
        title="Save JSON Snippet"
        description="Give this snippet a title so you can find it later."
        confirmLabel="Save"
        processingLabel="Saving…"
        processing={isSaving}
        formId={SAVE_FORM_ID}
      >
        <form id={SAVE_FORM_ID} onSubmit={onSubmit} className="space-y-4 py-2">
          <FormFieldRHF
            name="title"
            control={control}
            label="Snippet title"
            placeholder="e.g. User payload template"
            autoFocus
            rules={{ required: 'A title is required.' }}
          />
        </form>
      </FormDialog>
    </>
  );
}
