/**
 * useHookListener Hook
 *
 * Listens for real-time webhook events (inbound & outbound deliveries) via
 * Laravel Echo private channel. Updates local state when events arrive.
 *
 * @module Hook/Hooks/useHookListener
 */
import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

/**
 * @param {Function} onWebhookReceived   - Callback when an inbound webhook is received
 * @param {Function} onWebhookSent       - Callback when an outbound webhook delivery completes
 */
export function useHookListener({ onWebhookReceived, onWebhookSent }) {
  const { auth } = usePage().props;
  const userId = auth?.user?.id;

  const onReceivedRef = useRef(onWebhookReceived);
  const onSentRef = useRef(onWebhookSent);

  useEffect(() => {
    onReceivedRef.current = onWebhookReceived;
    onSentRef.current = onWebhookSent;
  }, [onWebhookReceived, onWebhookSent]);

  useEffect(() => {
    if (!userId || typeof window.Echo === 'undefined') return;

    const channel = window.Echo.private(`App.Models.User.${userId}`);

    channel.listen('.hook.webhook.received', (data) => {
      if (import.meta.env.DEV) {
        console.log('[HookListener] Webhook received:', data);
      }
      onReceivedRef.current?.(data.inboundRequest);
    });

    channel.listen('.hook.webhook.sent', (data) => {
      if (import.meta.env.DEV) {
        console.log('[HookListener] Webhook sent:', data);
      }
      onSentRef.current?.(data.outboundDelivery);
    });

    return () => {
      channel.stopListening('.hook.webhook.received');
      channel.stopListening('.hook.webhook.sent');
    };
  }, [userId]);
}

export default useHookListener;
