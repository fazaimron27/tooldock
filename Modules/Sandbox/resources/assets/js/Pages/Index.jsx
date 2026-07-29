import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

export default function Index() {
  const {
    entries: initialEntries = [],
    inbounds = [],
    probeHeader,
    tokenHeader,
    tokenEnabled,
    entriesEndpoint,
  } = usePage().props;

  const [entries, setEntries] = useState(initialEntries);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedRequestId, setSelectedRequestId] = useState(null);

  const curlSnippet = `curl -X POST <inbound-url> -H "${probeHeader}: 1" -H "Content-Type: application/json" -d '{"event":"inventory.adjustment","correlation_id":"2db9a0af-a90b-43cf-a9af-fb2c2e5f6d6f","occurred_at":"2026-04-11T10:00:00Z","warehouse":{"code":"WHS-JKT-01"},"items":[{"sku":"SKU-RED-001","delta":10,"unit_cost":120000},{"sku":"SKU-BLU-009","delta":5,"unit_cost":95000}]}'`;

  const totals = {
    all: entries.length,
    queued: entries.filter((entry) => entry.status === 'queued').length,
    applying: entries.filter((entry) => entry.status === 'applying').length,
    applied: entries.filter((entry) => entry.status === 'applied').length,
    reviewRequired: entries.filter((entry) => entry.status === 'review_required').length,
    failed: entries.filter((entry) => entry.status === 'failed').length,
  };

  useEffect(() => {
    setEntries(initialEntries);
  }, [initialEntries]);

  useEffect(() => {
    if (entries.length === 0) {
      setSelectedRequestId(null);
      return;
    }

    if (!selectedRequestId) {
      setSelectedRequestId(entries[0].request_id);
      return;
    }

    const selectedStillExists = entries.some((entry) => entry.request_id === selectedRequestId);

    if (!selectedStillExists) {
      setSelectedRequestId(entries[0].request_id);
    }
  }, [entries, selectedRequestId]);

  useEffect(() => {
    if (!entriesEndpoint) {
      return () => {};
    }

    const interval = window.setInterval(() => {
      if (document.hidden) {
        return;
      }

      fetch(entriesEndpoint, {
        headers: {
          Accept: 'application/json',
        },
        credentials: 'same-origin',
      })
        .then((response) => {
          if (!response.ok) {
            return null;
          }

          return response.json();
        })
        .then((payload) => {
          if (payload?.entries && Array.isArray(payload.entries)) {
            setEntries(payload.entries);
          }
        })
        .catch(() => {});
    }, 3000);

    return () => window.clearInterval(interval);
  }, [entriesEndpoint]);

  const getStatusVariant = (status) => {
    if (status === 'failed') {
      return 'destructive';
    }

    if (status === 'applied') {
      return 'default';
    }

    return 'outline-solid';
  };

  const formatTimestamp = (entry) => {
    const rawValue =
      entry.occurred_at ??
      entry.processed_at ??
      entry.created_at ??
      entry.updated_at ??
      entry.received_at ??
      null;

    if (!rawValue) {
      return '--';
    }

    const parsedDate = new Date(rawValue);

    if (Number.isNaN(parsedDate.getTime())) {
      return String(rawValue);
    }

    return parsedDate.toLocaleString();
  };

  const filteredEntries = useMemo(() => {
    const normalizedSearch = searchTerm.trim().toLowerCase();

    return entries.filter((entry) => {
      if (statusFilter !== 'all' && entry.status !== statusFilter) {
        return false;
      }

      if (!normalizedSearch) {
        return true;
      }

      const searchableText = [
        entry.request_id,
        entry.inbound_name,
        entry.inbound_slug,
        entry.method,
        entry.status,
        entry.source_ip,
        entry.processing?.summary,
        entry.processing?.output?.correlation_id,
        JSON.stringify(entry.processing?.output ?? {}),
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

      return searchableText.includes(normalizedSearch);
    });
  }, [entries, searchTerm, statusFilter]);

  const selectedEntry =
    filteredEntries.find((entry) => entry.request_id === selectedRequestId) ?? null;

  return (
    <PageShell
      title="Sandbox"
      description="Inventory adjustment intake monitor with downstream queue execution state."
    >
      <div className="space-y-6">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
          <Card className="xl:col-span-2">
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Total Events
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-semibold tracking-tight">{totals.all}</p>
              <p className="mt-1 text-xs text-muted-foreground">Live polling every 3 seconds.</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Queued</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-semibold tracking-tight">{totals.queued}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Applying</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-semibold tracking-tight">{totals.applying}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">Applied</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-semibold tracking-tight">{totals.applied}</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-3">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Review / Failed
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-3xl font-semibold tracking-tight">
                {totals.reviewRequired} / {totals.failed}
              </p>
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 gap-6 xl:grid-cols-5">
          <div className="space-y-6 xl:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle>Contract: inventory.adjustment.v1</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-sm text-muted-foreground">
                <p>
                  Required payload fields: event, correlation_id (uuid), occurred_at,
                  warehouse.code, items[].sku, items[].delta, items[].unit_cost.
                </p>
                <div className="space-y-1">
                  <p>Probe header:</p>
                  <p className="font-mono text-xs break-all text-foreground">{probeHeader}: 1</p>
                </div>
                <div className="space-y-1">
                  <p>Token check: {tokenEnabled ? 'enabled' : 'disabled'}</p>
                  <p className="font-mono text-xs break-all text-foreground">{tokenHeader}</p>
                </div>
                <pre className="rounded bg-muted p-3 text-[11px] leading-relaxed text-foreground whitespace-pre-wrap break-all">
                  {curlSnippet}
                </pre>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Available Inbounds</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {inbounds.length === 0 ? (
                  <p className="text-sm text-muted-foreground">
                    No active inbound endpoints found for your account.
                  </p>
                ) : (
                  inbounds.map((inbound) => (
                    <div key={inbound.id} className="rounded-md border p-3">
                      <div className="flex min-w-0 items-center justify-between gap-2">
                        <p className="min-w-0 truncate font-medium">{inbound.name}</p>
                        <Badge variant="outline" className="max-w-48 truncate">
                          {inbound.slug}
                        </Badge>
                      </div>
                      <p className="mt-1 font-mono text-xs break-all text-muted-foreground">
                        {inbound.url}
                      </p>
                    </div>
                  ))
                )}
              </CardContent>
            </Card>
          </div>

          <Card className="xl:col-span-3">
            <CardHeader>
              <CardTitle>Live Intake Reference Table</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
                <Input
                  value={searchTerm}
                  onChange={(event) => setSearchTerm(event.target.value)}
                  placeholder="Search request id, slug, correlation id, sku, source IP, summary..."
                  className="lg:max-w-xl"
                />
                <select
                  value={statusFilter}
                  onChange={(event) => setStatusFilter(event.target.value)}
                  className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
                >
                  <option value="all">All statuses</option>
                  <option value="queued">Queued</option>
                  <option value="applying">Applying</option>
                  <option value="applied">Applied</option>
                  <option value="review_required">Review required</option>
                  <option value="failed">Failed</option>
                </select>
                <p className="text-xs text-muted-foreground lg:ml-auto">
                  {filteredEntries.length} / {entries.length} rows
                </p>
              </div>

              {entries.length === 0 ? (
                <p className="text-sm text-muted-foreground">No probe events captured yet.</p>
              ) : (
                <>
                  <div className="rounded-md border overflow-x-auto">
                    <table className="w-full min-w-[980px] table-auto text-sm">
                      <thead>
                        <tr className="border-b bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                          <th className="px-3 py-2 text-left font-medium">Inbound</th>
                          <th className="px-3 py-2 text-left font-medium">Request ID</th>
                          <th className="px-3 py-2 text-left font-medium">Correlation</th>
                          <th className="px-3 py-2 text-left font-medium">Status</th>
                          <th className="px-3 py-2 text-left font-medium">Source</th>
                          <th className="px-3 py-2 text-left font-medium">Method</th>
                          <th className="px-3 py-2 text-left font-medium">Captured</th>
                          <th className="px-3 py-2 text-left font-medium">Summary</th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredEntries.length === 0 ? (
                          <tr>
                            <td colSpan={8} className="px-3 py-8 text-center text-muted-foreground">
                              No entries matched your filters.
                            </td>
                          </tr>
                        ) : (
                          filteredEntries.map((entry) => {
                            const isSelected = entry.request_id === selectedRequestId;

                            return (
                              <tr
                                key={entry.request_id}
                                className={`border-b align-top transition-colors ${
                                  isSelected ? 'bg-muted/50' : 'hover:bg-muted/30'
                                }`}
                                onClick={() => setSelectedRequestId(entry.request_id)}
                              >
                                <td className="px-3 py-2">
                                  <p className="font-medium">{entry.inbound_name}</p>
                                  <p className="text-xs text-muted-foreground">
                                    {entry.inbound_slug}
                                  </p>
                                </td>
                                <td className="px-3 py-2 font-mono text-xs">{entry.request_id}</td>
                                <td className="px-3 py-2 font-mono text-xs">
                                  {entry.processing?.output?.correlation_id ?? '--'}
                                </td>
                                <td className="px-3 py-2">
                                  <Badge variant={getStatusVariant(entry.status)}>
                                    {entry.status}
                                  </Badge>
                                </td>
                                <td className="px-3 py-2 font-mono text-xs text-muted-foreground">
                                  {entry.source_ip}
                                </td>
                                <td className="px-3 py-2">
                                  <Badge variant="outline">{entry.method}</Badge>
                                </td>
                                <td className="px-3 py-2 text-xs text-muted-foreground">
                                  {formatTimestamp(entry)}
                                </td>
                                <td className="px-3 py-2 text-xs text-muted-foreground max-w-md">
                                  <p className="line-clamp-2">
                                    {entry.processing?.summary ?? 'No processing summary.'}
                                  </p>
                                </td>
                              </tr>
                            );
                          })
                        )}
                      </tbody>
                    </table>
                  </div>

                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-base">Manual Review Pane</CardTitle>
                    </CardHeader>
                    <CardContent>
                      {selectedEntry ? (
                        <div className="space-y-3">
                          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div className="rounded-md border p-3 text-sm">
                              <p className="text-xs uppercase tracking-wide text-muted-foreground">
                                Selected Request
                              </p>
                              <p className="mt-1 font-mono text-xs break-all">
                                {selectedEntry.request_id}
                              </p>
                            </div>
                            <div className="rounded-md border p-3 text-sm">
                              <p className="text-xs uppercase tracking-wide text-muted-foreground">
                                Correlation ID
                              </p>
                              <p className="mt-1 font-mono text-xs break-all">
                                {selectedEntry.processing?.output?.correlation_id ?? '--'}
                              </p>
                            </div>
                          </div>
                          <pre className="overflow-x-auto rounded bg-muted p-3 text-[11px] leading-relaxed text-foreground">
                            {JSON.stringify(selectedEntry.processing?.output ?? {}, null, 2)}
                          </pre>
                        </div>
                      ) : (
                        <p className="text-sm text-muted-foreground">
                          Select a row from the table to inspect full payload output.
                        </p>
                      )}
                    </CardContent>
                  </Card>
                </>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </PageShell>
  );
}
