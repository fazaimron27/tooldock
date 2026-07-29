/**
 * Inbound Show Page
 *
 * Dedicated page for a single inbound endpoint.
 * URL: /hook/inbound/{id}  →  refreshing always restores this exact view.
 * Real-time: new inbound requests are prepended live via Echo.
 *
 * @module Hook/Pages/Inbound/Show
 */
import { useInertiaForm } from '@/Hooks/useInertiaForm';
import InboundRequestList from '@Hook/Components/InboundRequestList';
import { useHookListener } from '@Hook/Hooks/useHookListener';
import { updateInboundResolver } from '@Hook/Schemas/hookSchemas';
import { router, usePage } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Controller } from 'react-hook-form';

import FormDialog from '@/Components/Common/FormDialog';
import FormFieldRHF from '@/Components/Common/FormFieldRHF';
import PageShell from '@/Components/Layouts/PageShell';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

const EDIT_FORM_ID = 'inbound-edit-form';

export default function Show() {
  const { inbound, requests: initialRequests } = usePage().props;

  const [requests, setRequests] = useState(initialRequests || []);
  const [isEditOpen, setIsEditOpen] = useState(false);

  const editForm = useInertiaForm(
    {
      name: inbound.name,
      description: inbound.description ?? '',
      is_active: inbound.is_active ?? true,
    },
    {
      componentLevel: true,
      toast: { success: 'Inbound endpoint updated.' },
      resolver: updateInboundResolver,
    }
  );

  const onEditSubmit = editForm.handleSubmit((data) => {
    editForm.put(route('hook.inbound.update', inbound.id), {
      data,
      onSuccess: () => setIsEditOpen(false),
    });
  });

  useHookListener({
    onWebhookReceived: useCallback(
      (inboundRequest) => {
        if (inboundRequest.inbound_id !== inbound.id) return;
        setRequests((prev) => [inboundRequest, ...prev]);
      },
      [inbound.id]
    ),
    onWebhookSent: undefined,
  });

  return (
    <PageShell
      title={inbound.name}
      description="Inbound endpoint — real-time webhook inspector."
      actions={
        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => setIsEditOpen(true)}>
          <Pencil className="h-3.5 w-3.5" />
        </Button>
      }
    >
      <InboundRequestList
        inbound={inbound}
        requests={requests}
        loading={false}
        onBack={() => router.visit(route('hook.index', { tab: 'inbound' }), { replace: true })}
      />

      {/* ── Edit Dialog ── */}
      <FormDialog
        open={isEditOpen}
        onOpenChange={(open) => !open && setIsEditOpen(false)}
        onCancel={() => setIsEditOpen(false)}
        title="Edit Inbound Endpoint"
        description="Update the endpoint name, description or active status."
        formId={EDIT_FORM_ID}
        confirmLabel="Save Changes"
        processing={editForm.formState.isSubmitting}
        processingLabel="Saving…"
      >
        <form id={EDIT_FORM_ID} onSubmit={onEditSubmit} className="space-y-4">
          <FormFieldRHF name="name" control={editForm.control} label="Name" required />
          <FormFieldRHF
            name="description"
            control={editForm.control}
            label="Description"
            placeholder="Optional description…"
          />
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
    </PageShell>
  );
}
