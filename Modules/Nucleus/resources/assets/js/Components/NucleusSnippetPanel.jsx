/**
 * NucleusSnippetPanel
 *
 * History sidebar panel listing saved JSON snippets. Allows loading
 * a snippet back into the editor and deleting saved snippets.
 */
import { format } from 'date-fns';
import { Braces, Clock, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';

import ConfirmDialog from '@/Components/Common/ConfirmDialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

export default function NucleusSnippetPanel({ snippets, onLoad, onDelete }) {
  const [search, setSearch] = useState('');
  const [pendingDelete, setPendingDelete] = useState(null); // snippet to confirm

  const filtered = snippets.filter((s) => s.title.toLowerCase().includes(search.toLowerCase()));

  return (
    <div className="flex h-full flex-col bg-[#1e1e1e]">
      {/* Panel header */}
      <div className="border-b border-border px-4 py-3">
        <h3
          className="mb-2 text-xs font-semibold uppercase tracking-widest"
          style={{ color: 'rgba(255,255,255,0.4)' }}
        >
          Saved Snippets
        </h3>
        <div className="relative">
          <Search
            className="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2"
            style={{ color: 'rgba(255,255,255,0.35)' }}
          />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search snippets…"
            className="h-8 pl-8 text-xs bg-white/5 border-white/10 text-white placeholder:text-white/30 focus-visible:ring-white/20"
          />
        </div>
      </div>

      {/* Snippets list */}
      <div className="flex-1 overflow-y-auto">
        {filtered.length === 0 ? (
          <div className="flex flex-col items-center justify-center gap-2 py-12 text-center">
            <Braces className="h-8 w-8" style={{ color: 'rgba(255,255,255,0.2)' }} />
            <p className="text-[13px]" style={{ color: 'rgba(255,255,255,0.35)' }}>
              {search ? 'No matching snippets.' : 'No saved snippets yet.'}
            </p>
          </div>
        ) : (
          <div className="space-y-1 p-2">
            {filtered.map((snippet) => (
              <div
                key={snippet.id}
                className="group flex items-start justify-between rounded-md px-3 py-2.5 transition-colors hover:bg-white/6 cursor-pointer"
                onClick={() => onLoad(snippet.id)}
              >
                <div className="min-w-0 flex-1 pr-2">
                  <p
                    className="truncate text-xs font-medium"
                    style={{ color: 'rgba(255,255,255,0.88)' }}
                  >
                    {snippet.title}
                  </p>
                  <p
                    className="mt-0.5 flex items-center gap-1 text-[10px]"
                    style={{ color: 'rgba(255,255,255,0.38)' }}
                  >
                    <Clock className="h-2.5 w-2.5 shrink-0" />
                    {format(new Date(snippet.created_at), 'MMM d, yyyy · h:mm a')}
                  </p>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-6 w-6 shrink-0 opacity-0 transition-opacity group-hover:opacity-100 hover:text-destructive hover:bg-transparent"
                  onClick={(e) => {
                    e.stopPropagation();
                    setPendingDelete(snippet);
                  }}
                >
                  <Trash2 className="h-3 w-3" style={{ color: 'rgba(255,255,255,0.5)' }} />
                </Button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Delete confirmation */}
      <ConfirmDialog
        isOpen={pendingDelete !== null}
        title="Delete snippet?"
        message={
          pendingDelete
            ? `"${pendingDelete.title}" will be permanently deleted and cannot be recovered.`
            : ''
        }
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="destructive"
        onConfirm={() => {
          onDelete(pendingDelete.id);
          setPendingDelete(null);
        }}
        onCancel={() => setPendingDelete(null)}
      />
    </div>
  );
}
