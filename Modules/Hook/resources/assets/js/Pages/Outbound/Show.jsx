/**
 * Outbound Show Page
 *
 * Dedicated page for a single outbound webhook.
 * URL: /hook/outbound/{id}  →  refreshing always restores this exact view.
 * Real-time: delivery results are prepended live via Echo.
 *
 * @module Hook/Pages/Outbound/Show
 */
import OutboundWorkspace from '@Hook/Components/OutboundWorkspace';
import { useHookListener } from '@Hook/Hooks/useHookListener';
import { router, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';

import PageShell from '@/Components/Layouts/PageShell';

export default function Show() {
  const { outbound, deliveries: initialDeliveries, triggerDef } = usePage().props;

  const [deliveries, setDeliveries] = useState(initialDeliveries || []);

  useHookListener({
    onWebhookReceived: undefined,
    onWebhookSent: useCallback(
      (delivery) => {
        if (delivery.outbound_id !== outbound.id) return;
        setDeliveries((prev) => [delivery, ...prev]);
      },
      [outbound.id]
    ),
  });

  return (
    <PageShell
      title={outbound.name}
      description="Outbound webhook — send & inspect delivery history."
    >
      <OutboundWorkspace
        outbound={outbound}
        events={deliveries}
        loading={false}
        triggerDef={triggerDef}
        onBack={() => router.visit(route('hook.index', { tab: 'outbound' }), { replace: true })}
      />
    </PageShell>
  );
}
