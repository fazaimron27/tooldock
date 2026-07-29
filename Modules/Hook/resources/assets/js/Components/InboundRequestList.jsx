/**
 * InboundRequestList Component
 *
 * Displays a list of received inbound webhook requests for a selected
 * inbound endpoint. Each item is expandable to show full payload details.
 *
 * @module Hook/Components/InboundRequestList
 */
import PayloadViewer from '@Hook/Components/PayloadViewer';
import { format } from 'date-fns';
import { ArrowLeft } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';

const METHOD_COLORS = {
  GET: 'text-green-600 bg-green-500/10 border-green-500/20',
  POST: 'text-blue-600 bg-blue-500/10 border-blue-500/20',
  PUT: 'text-amber-600 bg-amber-500/10 border-amber-500/20',
  PATCH: 'text-orange-600 bg-orange-500/10 border-orange-500/20',
  DELETE: 'text-red-600 bg-red-500/10 border-red-500/20',
  HEAD: 'text-purple-600 bg-purple-500/10 border-purple-500/20',
  OPTIONS: 'text-gray-600 bg-gray-500/10 border-gray-500/20',
};

/**
 * @param {Object}   props
 * @param {Object}   props.inbound   - Selected inbound endpoint
 * @param {Array}    props.requests  - List of received requests
 * @param {boolean}  props.loading   - Whether requests are loading
 * @param {Function} props.onBack    - Go back to inbound list
 */
export default function InboundRequestList({ inbound, requests, loading, onBack }) {
  return (
    <div className="space-y-4">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={onBack}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h3 className="font-semibold">{inbound.name}</h3>
          <p className="text-xs text-muted-foreground">
            {requests.length} {requests.length === 1 ? 'request' : 'requests'} received
          </p>
        </div>
      </div>

      {loading ? (
        <Card>
          <CardContent className="py-8">
            <p className="text-center text-sm text-muted-foreground animate-pulse">
              Loading requests…
            </p>
          </CardContent>
        </Card>
      ) : requests.length > 0 ? (
        <div className="space-y-3">
          {requests.map((req) => (
            <Card key={req.id}>
              <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Badge
                      variant="outline"
                      className={`font-mono text-[10px] px-1.5 py-0 ${METHOD_COLORS[req.method] || ''}`}
                    >
                      {req.method}
                    </Badge>
                    <CardTitle className="text-sm font-mono truncate max-w-md">{req.url}</CardTitle>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-muted-foreground shrink-0">
                    <span>{req.source_ip}</span>
                    <span>{format(new Date(req.created_at), 'HH:mm:ss')}</span>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="pt-0 space-y-2">
                {req.content_type && (
                  <p className="text-xs text-muted-foreground">Content-Type: {req.content_type}</p>
                )}
                <PayloadViewer label="Headers" data={req.headers} />
                <PayloadViewer label="Payload" data={req.payload} defaultOpen />
                <PayloadViewer label="Query Params" data={req.query_params} />
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <Card>
          <CardContent className="py-8">
            <p className="text-center text-sm text-muted-foreground">
              No requests received yet. Send a webhook to this endpoint&apos;s URL to see it here.
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
