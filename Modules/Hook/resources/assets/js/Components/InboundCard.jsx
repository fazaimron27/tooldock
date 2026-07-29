/**
 * InboundCard Component
 *
 * Displays an inbound webhook endpoint card with its receive URL,
 * request count, and action buttons.
 *
 * @module Hook/Components/InboundCard
 */
import { Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { Check, Circle, Copy, Inbox, Link2, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

/**
 * Card for a single inbound webhook endpoint.
 *
 * @param {Object}   props
 * @param {Object}   props.inbound     - Inbound endpoint data
 * @param {string}   props.receiveUrl  - Full receive URL for this endpoint
 * @param {Function} props.onDelete    - Callback when delete is clicked
 */
export default function InboundCard({ inbound, receiveUrl, onDelete }) {
  const [copied, setCopied] = useState(false);

  const handleCopy = (e) => {
    e.preventDefault();
    e.stopPropagation();
    navigator.clipboard.writeText(receiveUrl);
    setCopied(true);
    setTimeout(() => setCopied(false), 1500);
  };

  return (
    <Link href={route('hook.inbound.show', inbound.id)} className="block">
      <Card
        className={`group cursor-pointer transition-all hover:shadow-md hover:border-primary/30 overflow-hidden ${
          inbound.is_active
            ? 'border-l-2 border-l-emerald-400'
            : 'border-l-2 border-l-muted-foreground/30'
        }`}
      >
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-2 min-w-0">
              <Inbox className="h-4 w-4 shrink-0 text-primary" />
              <CardTitle className="text-base truncate">{inbound.name}</CardTitle>
            </div>
            <Button
              variant="ghost"
              size="icon"
              className="h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive"
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                onDelete(inbound);
              }}
            >
              <Trash2 className="h-3.5 w-3.5" />
            </Button>
          </div>

          {/* Description — always reserve one line so all card heights match */}
          <CardDescription
            className={`text-xs line-clamp-1 mt-0.5 ${inbound.description ? '' : 'invisible select-none'}`}
          >
            {inbound.description ?? '\u00A0'}
          </CardDescription>
        </CardHeader>

        <CardContent className="pt-0 space-y-2">
          {/* Receive URL pill with copy button */}
          <div className="flex items-center gap-1.5 rounded-md bg-muted/50 px-2 py-1.5 text-xs font-mono">
            <Link2 className="h-3 w-3 shrink-0 text-muted-foreground" />
            <span className="truncate text-muted-foreground">{receiveUrl}</span>
            <Button
              variant="ghost"
              size="icon"
              className="h-5 w-5 shrink-0 ml-auto"
              onClick={handleCopy}
            >
              {copied ? <Check className="h-3 w-3 text-green-500" /> : <Copy className="h-3 w-3" />}
            </Button>
          </div>

          {/* Status row — always visible, right-aligned */}
          <div className="flex items-center justify-end gap-1.5 text-[11px] font-mono">
            <Circle
              className={`h-2 w-2 shrink-0 fill-current ${
                inbound.is_active ? 'text-emerald-500' : 'text-muted-foreground/40'
              }`}
            />
            <span
              className={
                inbound.is_active
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : 'text-muted-foreground/50'
              }
            >
              {inbound.is_active ? 'active' : 'inactive'}
            </span>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-between text-xs text-muted-foreground">
            <span>
              {inbound.inbound_requests_count ?? 0}{' '}
              {inbound.inbound_requests_count === 1 ? 'request' : 'requests'}
            </span>
            <span>{format(new Date(inbound.created_at), 'MMM d, yyyy')}</span>
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}
