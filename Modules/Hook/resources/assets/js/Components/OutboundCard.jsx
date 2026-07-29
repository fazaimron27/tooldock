/**
 * OutboundCard Component
 *
 * Displays an outbound webhook configuration card with target URL,
 * HTTP method, delivery count, and action buttons.
 *
 * @module Hook/Components/OutboundCard
 */
import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Send, Trash2, Zap } from 'lucide-react';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

const PROVIDER_COLORS = {
  telegram: 'text-sky-600 bg-sky-500/10',
  discord: 'text-indigo-600 bg-indigo-500/10',
  slack: 'text-emerald-600 bg-emerald-500/10',
};

const METHOD_COLORS = {
  GET: 'text-green-600 bg-green-500/10',
  POST: 'text-blue-600 bg-blue-500/10',
  PUT: 'text-amber-600 bg-amber-500/10',
  PATCH: 'text-orange-600 bg-orange-500/10',
  DELETE: 'text-red-600 bg-red-500/10',
};

/**
 * Card for a single outbound webhook configuration.
 *
 * @param {Object}   props
 * @param {Object}   props.outbound  - Outbound webhook data
 * @param {Function} props.onDelete  - Callback when delete is clicked
 */
export default function OutboundCard({ outbound, onDelete }) {
  return (
    <Link href={route('hook.outbound.show', outbound.id)} className="block">
      <Card
        className={`group cursor-pointer transition-all hover:shadow-md hover:border-primary/30 overflow-hidden border-l-2 ${
          !outbound.is_active
            ? 'border-l-muted-foreground/30'
            : outbound.trigger
              ? 'border-l-amber-400'
              : 'border-l-primary/30'
        }`}
      >
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-2 min-w-0">
              <Send className="h-4 w-4 shrink-0 text-primary" />
              <CardTitle className="text-base truncate">{outbound.name}</CardTitle>
            </div>
            <div className="flex items-center gap-1 shrink-0">
              {outbound.provider && outbound.provider !== 'generic' && (
                <Badge
                  className={`text-[10px] px-1.5 py-0 font-mono capitalize ${PROVIDER_COLORS[outbound.provider] ?? ''}`}
                  variant="outline"
                >
                  {outbound.provider}
                </Badge>
              )}
              <Badge
                className={`text-[10px] px-1.5 py-0 font-mono ${METHOD_COLORS[outbound.method] || ''}`}
                variant="outline"
              >
                {outbound.method}
              </Badge>
              <Button
                variant="ghost"
                size="icon"
                className="h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive"
                onClick={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  onDelete(outbound);
                }}
              >
                <Trash2 className="h-3.5 w-3.5" />
              </Button>
            </div>
          </div>
          {/* Description — always reserve one line height so cards align */}
          <CardDescription
            className={`text-xs line-clamp-1 mt-0.5 ${outbound.description ? '' : 'invisible select-none'}`}
          >
            {outbound.description ?? '\u00A0'}
          </CardDescription>
        </CardHeader>
        <CardContent className="pt-0 space-y-2">
          {/* URL pill */}
          <div className="rounded-md bg-muted/50 px-2 py-1.5 text-xs font-mono truncate text-muted-foreground">
            {outbound.display_url ?? '—'}
          </div>

          {/* Trigger / status row — always visible */}
          <div className="flex items-center justify-end gap-1.5 text-[11px] font-mono">
            {!outbound.is_active ? (
              <span className="text-muted-foreground/60">inactive</span>
            ) : outbound.trigger ? (
              <>
                <Zap className="h-3 w-3 shrink-0 text-amber-500" />
                <span className="truncate text-amber-600 dark:text-amber-400">
                  {outbound.trigger}
                </span>
              </>
            ) : (
              <span className="text-muted-foreground/50">manual</span>
            )}
          </div>

          {/* Footer */}
          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground">
              {outbound.deliveries_count ?? 0}{' '}
              {outbound.deliveries_count === 1 ? 'delivery' : 'deliveries'}
            </span>
            <span className="text-xs text-muted-foreground">
              {format(new Date(outbound.created_at), 'MMM d, yyyy')}
            </span>
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}
