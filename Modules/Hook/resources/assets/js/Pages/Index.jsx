/**
 * Hook Module Index Page
 *
 * Main dashboard showing all inbound endpoints and outbound webhooks.
 * Clicking a card navigates to its dedicated show page (/hook/inbound/{id}
 * or /hook/outbound/{id}), which is URL-routable and refresh-safe.
 *
 * @module Hook/Pages/Index
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import InboundCard from '@Hook/Components/InboundCard';
import OutboundCard from '@Hook/Components/OutboundCard';
import { useHookListener } from '@Hook/Hooks/useHookListener';
import { createInboundResolver, createOutboundResolver } from '@Hook/Schemas/hookSchemas';
import { router, usePage } from '@inertiajs/react';
import { Inbox, Send, Zap } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Controller } from 'react-hook-form';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Label } from '@/Components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

const INBOUND_FORM_ID = 'hook-inbound-form';
const OUTBOUND_FORM_ID = 'hook-outbound-form';

export default function Index() {
  const {
    inbounds: initialInbounds,
    outbounds: initialOutbounds,
    receiveBaseUrl,
    triggers = [],
    providers = [],
  } = usePage().props;
  const { url } = usePage();

  const activeTab =
    new URLSearchParams(url.split('?')[1] ?? '').get('tab') === 'outbound' ? 'outbound' : 'inbound';

  const handleTabChange = useCallback((tab) => {
    router.get(
      route('hook.index'),
      { tab },
      { preserveState: true, preserveScroll: true, replace: true }
    );
  }, []);

  const [inbounds, setInbounds] = useState(initialInbounds || []);
  const [outbounds, setOutbounds] = useState(initialOutbounds || []);
  useEffect(() => {
    setInbounds(initialInbounds || []);
  }, [initialInbounds]);

  useEffect(() => {
    setOutbounds(initialOutbounds || []);
  }, [initialOutbounds]);

  const [isInboundDialogOpen, setIsInboundDialogOpen] = useState(false);
  const [isOutboundDialogOpen, setIsOutboundDialogOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteType, setDeleteType] = useState(null);

  const inboundForm = useInertiaForm(
    { name: '', description: '' },
    {
      componentLevel: true,
      toast: { success: 'Inbound endpoint created.' },
      resolver: createInboundResolver,
    }
  );

  const outboundForm = useInertiaForm(
    {
      name: '',
      provider: 'generic',
      provider_config: {},
      target_url: '',
      method: 'POST',
      trigger: '__none__',
      description: '',
    },
    {
      componentLevel: true,
      toast: { success: 'Outbound webhook created.' },
      resolver: createOutboundResolver,
    }
  );

  const watchedProvider = outboundForm.watch('provider');
  const selectedProvider = providers.find((p) => p.value === watchedProvider) ?? providers[0];

  useHookListener({
    onWebhookReceived: useCallback((inboundRequest) => {
      setInbounds((prev) =>
        prev.map((i) =>
          i.id === inboundRequest.inbound_id
            ? { ...i, inbound_requests_count: (i.inbound_requests_count ?? 0) + 1 }
            : i
        )
      );
    }, []),
    onWebhookSent: useCallback((delivery) => {
      setOutbounds((prev) =>
        prev.map((o) =>
          o.id === delivery.outbound_id
            ? { ...o, deliveries_count: (o.deliveries_count ?? 0) + 1 }
            : o
        )
      );
    }, []),
  });

  const onCreateInbound = inboundForm.handleSubmit((data) => {
    inboundForm.post(route('hook.inbound.store'), {
      data,
      onSuccess: () => {
        setIsInboundDialogOpen(false);
        inboundForm.reset({ name: '', description: '' });
      },
    });
  });

  const handleDeleteInbound = useCallback(() => {
    if (!deleteTarget) return;
    router.delete(route('hook.inbound.destroy', deleteTarget.id), {
      onSuccess: () => {
        setDeleteTarget(null);
        setDeleteType(null);
      },
    });
  }, [deleteTarget]);

  const onCreateOutbound = outboundForm.handleSubmit((data) => {
    const trigger = data.trigger === '__none__' ? null : data.trigger || null;
    const payload = {
      ...data,
      trigger,
      payload_template: data.payload_template ?? selectedProvider?.defaultPayload ?? {},
    };
    outboundForm.post(route('hook.outbound.store'), {
      data: payload,
      onSuccess: () => {
        setIsOutboundDialogOpen(false);
        outboundForm.reset({
          name: '',
          provider: 'generic',
          provider_config: {},
          target_url: '',
          method: 'POST',
          trigger: '__none__',
          description: '',
        });
      },
    });
  });

  const handleDeleteOutbound = useCallback(() => {
    if (!deleteTarget) return;
    router.delete(route('hook.outbound.destroy', deleteTarget.id), {
      onSuccess: () => {
        setDeleteTarget(null);
        setDeleteType(null);
      },
    });
  }, [deleteTarget]);

  return (
    <PageShell
      title="Hook"
      description="Bidirectional webhook tool — receive inbound, send outbound."
      actions={
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => {
              inboundForm.reset({ name: '', description: '' });
              setIsInboundDialogOpen(true);
            }}
          >
            <Inbox className="mr-2 h-4 w-4" />
            New Inbound
          </Button>
          <Button
            onClick={() => {
              outboundForm.reset({
                name: '',
                provider: 'generic',
                provider_config: {},
                target_url: '',
                method: 'POST',
                trigger: '__none__',
                description: '',
              });
              setIsOutboundDialogOpen(true);
            }}
          >
            <Send className="mr-2 h-4 w-4" />
            New Outbound
          </Button>
        </div>
      }
    >
      <Tabs value={activeTab} onValueChange={handleTabChange} className="space-y-4">
        <TabsList>
          <TabsTrigger value="inbound">
            <Inbox className="mr-1.5 h-4 w-4" />
            Inbound
            {inbounds.length > 0 && (
              <span className="ml-1.5 text-xs text-muted-foreground">({inbounds.length})</span>
            )}
          </TabsTrigger>
          <TabsTrigger value="outbound">
            <Send className="mr-1.5 h-4 w-4" />
            Outbound
            {outbounds.length > 0 && (
              <span className="ml-1.5 text-xs text-muted-foreground">({outbounds.length})</span>
            )}
          </TabsTrigger>
        </TabsList>

        {/* ── Inbound Tab ── */}
        <TabsContent value="inbound">
          {inbounds.length > 0 ? (
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {inbounds.map((inbound) => (
                <InboundCard
                  key={inbound.id}
                  inbound={inbound}
                  receiveUrl={`${receiveBaseUrl}/${inbound.slug}`}
                  onDelete={(i) => {
                    setDeleteTarget(i);
                    setDeleteType('inbound');
                  }}
                />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="py-12">
                <div className="flex flex-col items-center justify-center text-center">
                  <Inbox className="w-10 h-10 mx-auto text-muted-foreground mb-2" />
                  <p className="text-muted-foreground mb-3">
                    Create your first inbound endpoint to start receiving webhooks.
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* ── Outbound Tab ── */}
        <TabsContent value="outbound">
          {outbounds.length > 0 ? (
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {outbounds.map((outbound) => (
                <OutboundCard
                  key={outbound.id}
                  outbound={outbound}
                  onDelete={(o) => {
                    setDeleteTarget(o);
                    setDeleteType('outbound');
                  }}
                />
              ))}
            </div>
          ) : (
            <Card>
              <CardContent className="py-12">
                <div className="flex flex-col items-center justify-center text-center">
                  <Send className="w-10 h-10 mx-auto text-muted-foreground mb-2" />
                  <p className="text-muted-foreground mb-3">
                    Create your first outbound webhook to start sending.
                  </p>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>
      </Tabs>

      {/* ── Create Inbound Dialog ── */}
      <FormDialog
        open={isInboundDialogOpen}
        onOpenChange={(open) => !open && setIsInboundDialogOpen(false)}
        onCancel={() => {
          setIsInboundDialogOpen(false);
          inboundForm.reset({ name: '', description: '' });
        }}
        title="New Inbound Endpoint"
        description="Create a unique URL to receive and inspect inbound webhook payloads."
        formId={INBOUND_FORM_ID}
        confirmLabel="Create Inbound"
        processing={inboundForm.formState.isSubmitting}
        processingLabel="Creating..."
      >
        <form id={INBOUND_FORM_ID} onSubmit={onCreateInbound} className="space-y-4">
          <FormFieldRHF
            name="name"
            control={inboundForm.control}
            label="Name"
            required
            placeholder="e.g. Stripe Webhooks"
          />
          <FormFieldRHF
            name="description"
            control={inboundForm.control}
            label="Description"
            placeholder="Optional description..."
          />
        </form>
      </FormDialog>

      {/* ── Create Outbound Dialog ── */}
      <FormDialog
        open={isOutboundDialogOpen}
        onOpenChange={(open) => !open && setIsOutboundDialogOpen(false)}
        onCancel={() => {
          setIsOutboundDialogOpen(false);
          outboundForm.reset({
            name: '',
            provider: 'generic',
            provider_config: {},
            target_url: '',
            method: 'POST',
            trigger: '__none__',
            description: '',
          });
        }}
        title="New Outbound Webhook"
        description="Configure a webhook to send to an external endpoint."
        formId={OUTBOUND_FORM_ID}
        confirmLabel="Create Outbound"
        processing={outboundForm.formState.isSubmitting}
        processingLabel="Creating..."
      >
        <form id={OUTBOUND_FORM_ID} onSubmit={onCreateOutbound} className="space-y-4">
          <FormFieldRHF
            name="name"
            control={outboundForm.control}
            label="Name"
            required
            placeholder="e.g. Payment Notification"
          />
          {/* Provider */}
          <div className="space-y-2">
            <Label>Provider</Label>
            <Controller
              name="provider"
              control={outboundForm.control}
              render={({ field }) => (
                <Select
                  value={field.value}
                  onValueChange={(v) => {
                    field.onChange(v);
                    outboundForm.setValue('provider_config', {});
                    const prov = providers.find((p) => p.value === v);
                    if (prov?.defaultPayload) {
                      outboundForm.setValue('payload_template', prov.defaultPayload);
                    }
                  }}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select provider" />
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

          {/* Generic: manual URL */}
          {watchedProvider === 'generic' && (
            <FormFieldRHF
              name="target_url"
              control={outboundForm.control}
              label="Target URL"
              required
              placeholder="https://example.com/webhook"
            />
          )}

          {/* Managed provider credential fields */}
          {selectedProvider?.configFields?.map((field) => (
            <div key={field.key} className="space-y-2">
              <Label>{field.label}</Label>
              <Controller
                name={`provider_config.${field.key}`}
                control={outboundForm.control}
                rules={{ required: `${field.label} is required` }}
                render={({ field: f, fieldState }) => (
                  <>
                    <input
                      type={field.type}
                      placeholder={field.placeholder}
                      value={f.value ?? ''}
                      onChange={f.onChange}
                      className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                    />
                    {fieldState.error && (
                      <p className="text-[11px] text-destructive">{fieldState.error.message}</p>
                    )}
                  </>
                )}
              />
            </div>
          ))}

          {/* HTTP Method */}
          <div className="space-y-2">
            <Label>HTTP Method</Label>
            <Controller
              name="method"
              control={outboundForm.control}
              render={({ field }) => (
                <Select value={field.value} onValueChange={field.onChange}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select method" />
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
                control={outboundForm.control}
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
              <p className="text-[11px] text-muted-foreground">
                When set, this webhook fires automatically on the selected event.
              </p>
            </div>
          )}

          <FormFieldRHF
            name="description"
            control={outboundForm.control}
            label="Description"
            placeholder="Optional description..."
          />
        </form>
      </FormDialog>

      {/* ── Delete Confirmation Dialog ── */}
      <ConfirmDialog
        isOpen={!!deleteTarget}
        onCancel={() => {
          setDeleteTarget(null);
          setDeleteType(null);
        }}
        title={`Delete ${deleteType === 'inbound' ? 'Inbound Endpoint' : 'Outbound Webhook'}`}
        message={`Are you sure you want to delete "${deleteTarget?.name}"? This action cannot be undone.`}
        onConfirm={deleteType === 'inbound' ? handleDeleteInbound : handleDeleteOutbound}
        confirmLabel="Delete"
        variant="destructive"
      />
    </PageShell>
  );
}
