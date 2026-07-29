/**
 * OutboundWorkspace Component
 *
 * Interactive workspace for a selected outbound webhook configuration.
 * Fixed config: target URL + HTTP method.
 * Editable per-send: headers and payload (Monaco JSON editors).
 * Headers and payload are persisted to DB via the Save button.
 *
 * @module Hook/Components/OutboundWorkspace
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import OutboundDeliveryList from '@Hook/Components/OutboundDeliveryList';
import { updateOutboundResolver } from '@Hook/Schemas/hookSchemas';
import { router, usePage } from '@inertiajs/react';
import Editor from '@monaco-editor/react';
import axios from 'axios';
import { ArrowLeft, Pencil, Save, Send, Zap } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { Controller } from 'react-hook-form';
import { toast } from 'sonner';

import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

const METHOD_COLORS = {
  GET: 'bg-green-500/10 text-green-600 border-green-500/30',
  POST: 'bg-blue-500/10 text-blue-600 border-blue-500/30',
  PUT: 'bg-amber-500/10 text-amber-600 border-amber-500/30',
  PATCH: 'bg-orange-500/10 text-orange-600 border-orange-500/30',
  DELETE: 'bg-red-500/10 text-red-600 border-red-500/30',
};

const DEFAULT_HEADERS = JSON.stringify({ 'Content-Type': 'application/json' }, null, 2);

/** Safe JSON parse — undefined means parse error, null means empty. */
function tryParse(str) {
  if (!str || !str.trim()) return null;
  try {
    return JSON.parse(str);
  } catch {
    return undefined;
  }
}

/**
 * Styled code-editor chrome panel — macOS-style header bar with traffic-light
 * dots, filename, JSON badge, and an optional inline error hint.
 */
function EditorPanel({ title, error, defaultValue, onChange }) {
  return (
    <div
      className={`rounded-lg border overflow-hidden shadow-xs transition-colors ${
        error ? 'border-destructive' : 'border-border'
      }`}
    >
      {/* ── Chrome bar ── */}
      <div className="flex items-center justify-between gap-2 bg-[#1e1e1e] border-b border-white/6 px-3 py-2">
        <div className="flex items-center gap-2.5">
          <span className="flex gap-1.5">
            <span className="h-2.5 w-2.5 rounded-full bg-[#ff5f57]" />
            <span className="h-2.5 w-2.5 rounded-full bg-[#febc2e]" />
            <span className="h-2.5 w-2.5 rounded-full bg-[#28c840]" />
          </span>
          <span className="text-[11px] text-white/40 font-mono">{title.toLowerCase()}.json</span>
        </div>
        <div className="flex items-center gap-2">
          {error && <span className="text-[10px] text-destructive font-medium">{error}</span>}
          <span className="rounded bg-white/6 px-1.5 py-0.5 text-[10px] text-white/30 font-mono uppercase tracking-widest">
            json
          </span>
        </div>
      </div>

      {/* ── Monaco editor ── */}
      <Editor
        height="200px"
        defaultLanguage="json"
        defaultValue={defaultValue}
        onChange={onChange}
        theme="vs-dark"
        options={{
          minimap: { enabled: false },
          scrollBeyondLastLine: false,
          fontSize: 13,
          fontFamily: '"Geist Mono", "JetBrains Mono", "Fira Code", ui-monospace, monospace',
          lineNumbers: 'on',
          lineNumbersMinChars: 3,
          folding: true,
          wordWrap: 'on',
          tabSize: 2,
          renderLineHighlight: 'line',
          overviewRulerLanes: 0,
          padding: { top: 10, bottom: 10 },
          scrollbar: { vertical: 'auto', horizontal: 'hidden', verticalScrollbarSize: 4 },
          bracketPairColorization: { enabled: true },
          guides: { bracketPairs: true },
        }}
      />
    </div>
  );
}

const EDIT_FORM_ID = 'outbound-edit-form';

/**
 * @param {Object}   props
 * @param {Object}   props.outbound    - The selected outbound webhook (fixed config)
 * @param {Array}    props.events      - Delivery records (real-time updated from parent)
 * @param {boolean}  props.loading     - Whether delivery list is still loading
 * @param {Object}   [props.triggerDef]- Trigger definition from HookEventRegistry (label + payloadSchema)
 * @param {Function} props.onBack      - Navigate back to outbound list
 */
export default function OutboundWorkspace({ outbound, events, loading, triggerDef, onBack }) {
  const { props: pageProps } = usePage();
  const providers = pageProps.providers ?? [];
  const triggers = pageProps.triggers ?? [];

  const [isEditOpen, setIsEditOpen] = useState(false);

  const editForm = useInertiaForm(
    {
      name: outbound.name,
      description: outbound.description ?? '',
      provider: outbound.provider ?? 'generic',
      provider_config: {},
      target_url: outbound.target_url ?? '',
      method: outbound.method,
      trigger: outbound.trigger ?? '__none__',
      is_active: outbound.is_active ?? true,
    },
    {
      componentLevel: true,
      toast: { success: 'Outbound webhook updated.' },
      resolver: updateOutboundResolver,
    }
  );

  const watchedProvider = editForm.watch('provider');
  const selectedProvider = providers.find((p) => p.value === watchedProvider);

  const onEditSubmit = editForm.handleSubmit((data) => {
    const payload = {
      ...data,
      trigger: data.trigger === '__none__' ? null : data.trigger || null,
      provider_config: Object.values(data.provider_config ?? {}).some(Boolean)
        ? data.provider_config
        : undefined,
    };
    editForm.put(route('hook.outbound.update', outbound.id), {
      data: payload,
      onSuccess: () => {
        setIsEditOpen(false);
        router.reload({ only: ['outbound'] });
      },
    });
  });
  const [headersError, setHeadersError] = useState(null);
  const [payloadError, setPayloadError] = useState(null);
  const [isSending, setIsSending] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  const providerMeta = providers.find((p) => p.value === (outbound.provider ?? 'generic'));
  const defaultPayload = JSON.stringify(
    providerMeta?.defaultPayload ?? { event: 'test', data: {} },
    null,
    2
  );

  const headersRef = useRef(
    outbound.headers ? JSON.stringify(outbound.headers, null, 2) : DEFAULT_HEADERS
  );
  const payloadRef = useRef(
    outbound.payload_template ? JSON.stringify(outbound.payload_template, null, 2) : defaultPayload
  );

  const validateEditors = useCallback(() => {
    const parsedHeaders = tryParse(headersRef.current);
    const parsedPayload = tryParse(payloadRef.current);
    let valid = true;

    if (parsedHeaders === undefined) {
      setHeadersError('Invalid JSON');
      valid = false;
    } else setHeadersError(null);

    if (parsedPayload === undefined) {
      setPayloadError('Invalid JSON');
      valid = false;
    } else setPayloadError(null);

    return valid ? { parsedHeaders, parsedPayload } : null;
  }, []);

  const handleSave = useCallback(async () => {
    const result = validateEditors();
    if (!result) return;

    setIsSaving(true);
    try {
      await axios.put(route('hook.outbound.update', outbound.id), {
        headers: result.parsedHeaders,
        payload_template: result.parsedPayload,
      });
      toast.success('Saved — headers and payload persisted.');
    } catch (err) {
      toast.error('Failed to save', {
        description: err.response?.data?.message || err.message,
      });
    } finally {
      setIsSaving(false);
    }
  }, [outbound.id, validateEditors]);

  const handleSend = useCallback(async () => {
    const result = validateEditors();
    if (!result) return;

    setIsSending(true);
    try {
      const response = await axios.post(route('hook.outbound.send', outbound.id), {
        headers: result.parsedHeaders,
        payload: result.parsedPayload,
      });
      toast.info(response.data.message);
    } catch (err) {
      toast.error('Failed to send', {
        description: err.response?.data?.message || err.message,
      });
    } finally {
      setIsSending(false);
    }
  }, [outbound.id, validateEditors]);

  return (
    <div className="space-y-6">
      {/* ── Header ── */}
      <div className="flex items-center justify-between gap-4">
        <div className="flex items-center gap-3 min-w-0">
          <Button variant="ghost" size="icon" className="h-8 w-8 shrink-0" onClick={onBack}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div className="min-w-0">
            <h3 className="font-semibold">{outbound.name}</h3>
            <div className="flex items-center gap-2 mt-0.5">
              <Badge
                variant="outline"
                className={`font-mono text-[10px] px-1.5 py-0 shrink-0 ${METHOD_COLORS[outbound.method] ?? ''}`}
              >
                {outbound.method}
              </Badge>
              <span className="font-mono text-xs text-muted-foreground truncate">
                {outbound.display_url ?? '—'}
              </span>
            </div>
          </div>
        </div>

        {/* Action buttons */}
        <div className="flex items-center gap-2 shrink-0">
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8"
            onClick={() => setIsEditOpen(true)}
          >
            <Pencil className="h-3.5 w-3.5" />
          </Button>
          <Button variant="outline" size="sm" onClick={handleSave} disabled={isSaving}>
            <Save className="mr-1.5 h-3.5 w-3.5" />
            {isSaving ? 'Saving…' : 'Save'}
          </Button>
          <Button onClick={handleSend} disabled={isSending} size="sm">
            <Send className="mr-1.5 h-3.5 w-3.5" />
            {isSending ? 'Sending…' : 'Send'}
          </Button>
        </div>
      </div>

      {/* ── Edit Dialog ── */}
      <FormDialog
        open={isEditOpen}
        onOpenChange={(open) => !open && setIsEditOpen(false)}
        onCancel={() => setIsEditOpen(false)}
        title="Edit Outbound Webhook"
        description="Update the webhook configuration."
        formId={EDIT_FORM_ID}
        confirmLabel="Save Changes"
        processing={editForm.formState.isSubmitting}
        processingLabel="Saving…"
      >
        <form id={EDIT_FORM_ID} onSubmit={onEditSubmit} className="space-y-4">
          <FormFieldRHF name="name" control={editForm.control} label="Name" required />

          {/* Provider */}
          <div className="space-y-2">
            <Label>Provider</Label>
            <Controller
              name="provider"
              control={editForm.control}
              render={({ field }) => (
                <Select
                  value={field.value}
                  onValueChange={(v) => {
                    field.onChange(v);
                    editForm.setValue('provider_config', {});
                  }}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {providers.map((p) => (
                      <SelectItem key={p.value} value={p.value}>
                        {p.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>

          {/* Generic: URL */}
          {watchedProvider === 'generic' && (
            <FormFieldRHF
              name="target_url"
              control={editForm.control}
              label="Target URL"
              required
            />
          )}

          {/* Managed credential fields */}
          {selectedProvider?.configFields?.map((field) => (
            <div key={field.key} className="space-y-2">
              <Label>{field.label}</Label>
              <Controller
                name={`provider_config.${field.key}`}
                control={editForm.control}
                render={({ field: f }) => (
                  <input
                    type={field.type}
                    placeholder={`Leave blank to keep existing ${field.label}`}
                    value={f.value ?? ''}
                    onChange={f.onChange}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                  />
                )}
              />
            </div>
          ))}

          {/* Method */}
          <div className="space-y-2">
            <Label>HTTP Method</Label>
            <Controller
              name="method"
              control={editForm.control}
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {['GET', 'POST', 'PUT', 'PATCH', 'DELETE'].map((m) => (
                      <SelectItem key={m} value={m}>
                        {m}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            />
          </div>

          {/* Auto-trigger */}
          {triggers.length > 0 && (
            <div className="space-y-2">
              <Label>
                Auto Trigger <span className="text-muted-foreground font-normal">(optional)</span>
              </Label>
              <Controller
                name="trigger"
                control={editForm.control}
                render={({ field }) => (
                  <Select value={field.value} onValueChange={field.onChange}>
                    <SelectTrigger>
                      <SelectValue placeholder="None — manual only" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="__none__">None — manual only</SelectItem>
                      {triggers.map((t) => (
                        <SelectItem key={t.key} value={t.key}>
                          <span className="flex items-center gap-1.5">
                            <Zap className="h-3 w-3 text-amber-500" />
                            {t.label}
                          </span>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                )}
              />
            </div>
          )}

          <FormFieldRHF
            name="description"
            control={editForm.control}
            label="Description"
            placeholder="Optional description…"
          />

          {/* Active toggle */}
          <div className="flex items-center justify-between">
            <Label>Active</Label>
            <Controller
              name="is_active"
              control={editForm.control}
              render={({ field }) => (
                <Switch checked={field.value} onCheckedChange={field.onChange} />
              )}
            />
          </div>
        </form>
      </FormDialog>

      {/* ── Trigger Info Panel ── */}
      {triggerDef && (
        <div className="rounded-lg border border-amber-500/20 bg-amber-500/5 p-4 space-y-3">
          <div className="flex items-center gap-2">
            <Zap className="h-3.5 w-3.5 text-amber-500 shrink-0" />
            <span className="text-sm font-medium">{triggerDef.label}</span>
            <span className="text-xs text-muted-foreground ml-auto">Auto-fires on this event</span>
          </div>

          {triggerDef.payloadSchema?.length > 0 && (
            <div className="space-y-1.5">
              <p className="text-xs text-muted-foreground">
                Available in <code className="font-mono text-amber-600">_data</code> at runtime:
              </p>
              <div className="flex flex-wrap gap-1">
                {triggerDef.payloadSchema.map((field) => (
                  <code
                    key={field}
                    className="rounded bg-amber-500/10 px-1.5 py-0.5 text-[11px] font-mono text-amber-700 dark:text-amber-400"
                  >
                    {field}
                  </code>
                ))}
              </div>
            </div>
          )}

          <div className="rounded bg-black/20 p-2 text-[11px] font-mono text-muted-foreground leading-relaxed">
            <span className="text-white/50">// Payload structure sent automatically:</span>
            <br />
            {'{ "_trigger": "'}
            <span className="text-amber-400">{outbound.trigger}</span>
            {'", "_data": { ... }, ...your template }'}
          </div>
        </div>
      )}

      {/* ── Monaco Editor Panels ── */}
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <EditorPanel
          title="Headers"
          error={headersError}
          defaultValue={headersRef.current}
          onChange={(val) => {
            headersRef.current = val ?? '';
            if (headersError) setHeadersError(null);
          }}
        />
        <EditorPanel
          title="Payload"
          error={payloadError}
          defaultValue={payloadRef.current}
          onChange={(val) => {
            payloadRef.current = val ?? '';
            if (payloadError) setPayloadError(null);
          }}
        />
      </div>

      {/* ── Delivery History ── */}
      <div>
        <h4 className="text-sm font-medium text-muted-foreground mb-3">Delivery History</h4>
        <OutboundDeliveryList
          outbound={outbound}
          events={events}
          loading={loading}
          onBack={onBack}
          hideHeader
        />
      </div>
    </div>
  );
}
