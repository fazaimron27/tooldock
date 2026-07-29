/**
 * OutboundDeliveryList Component
 *
 * Displays a list of outbound webhook delivery results for a selected
 * outbound webhook. Embeddable inside OutboundWorkspace via hideHeader prop.
 *
 * @module Hook/Components/OutboundDeliveryList
 */
import PayloadViewer from '@Hook/Components/PayloadViewer';
import { format } from 'date-fns';
import { AlertCircle, ArrowLeft, CheckCircle, Clock, XCircle } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';

function getStatusInfo(status, errorMessage) {
  if (errorMessage) {
    return { label: 'Error', variant: 'destructive', icon: AlertCircle };
  }
  if (!status) {
    return { label: 'Unknown', variant: 'secondary', icon: XCircle };
  }
  if (status >= 200 && status < 300) {
    return { label: String(status), variant: 'default', icon: CheckCircle };
  }
  if (status >= 400) {
    return { label: String(status), variant: 'destructive', icon: XCircle };
  }
  return { label: String(status), variant: 'secondary', icon: AlertCircle };
}

/**
 * @param {Object}   props
 * @param {Object}   props.outbound    - Selected outbound webhook
 * @param {Array}    props.events      - List of delivery records
 * @param {boolean}  props.loading     - Whether deliveries are loading
 * @param {Function} props.onBack      - Go back to outbound list
 * @param {boolean}  [props.hideHeader=false] - Hide header when embedded
 */
export default function OutboundDeliveryList({
  outbound,
  events,
  loading,
  onBack,
  hideHeader = false,
}) {
  return (
    <div className="space-y-4">
      {!hideHeader && (
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" className="h-8 w-8" onClick={onBack}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h3 className="font-semibold">{outbound.name}</h3>
            <p className="text-xs text-muted-foreground">
              {events.length} {events.length === 1 ? 'delivery' : 'deliveries'}
            </p>
          </div>
        </div>
      )}

      {loading ? (
        <Card>
          <CardContent className="py-8">
            <p className="text-center text-sm text-muted-foreground animate-pulse">
              Loading deliveries…
            </p>
          </CardContent>
        </Card>
      ) : events.length > 0 ? (
        <div className="space-y-3">
          {events.map((evt) => {
            const statusInfo = getStatusInfo(evt.response_status, evt.error_message);
            const StatusIcon = statusInfo.icon;

            return (
              <Card key={evt.id}>
                <CardHeader className="pb-2">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <Badge variant={statusInfo.variant} className="text-[10px] px-1.5 py-0 gap-1">
                        <StatusIcon className="h-3 w-3" />
                        {statusInfo.label}
                      </Badge>
                      {evt.duration_ms != null && (
                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                          <Clock className="h-3 w-3" />
                          {evt.duration_ms}ms
                        </span>
                      )}
                    </div>
                    <span className="text-xs text-muted-foreground">
                      {format(new Date(evt.created_at), 'HH:mm:ss')}
                    </span>
                  </div>
                </CardHeader>
                <CardContent className="pt-0 space-y-2">
                  {evt.error_message && (
                    <div className="rounded-md bg-destructive/10 px-3 py-2 text-xs text-destructive">
                      {evt.error_message}
                    </div>
                  )}
                  <PayloadViewer label="Response Headers" data={evt.response_headers} />
                  <PayloadViewer label="Response Body" data={evt.response_body} defaultOpen />
                </CardContent>
              </Card>
            );
          })}
        </div>
      ) : (
        <Card>
          <CardContent className="py-8">
            <p className="text-center text-sm text-muted-foreground">
              No deliveries yet. Click &ldquo;Send&rdquo; to fire this outbound webhook.
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
