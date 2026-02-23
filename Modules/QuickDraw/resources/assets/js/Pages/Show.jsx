/**
 * QuickDraw Show Page — tldraw Whiteboard Editor
 *
 * tldraw canvas wrapped in the dashboard layout with auto-save persistence.
 * Loads the document state from props on mount and syncs changes via useAutoSave.
 * Displays a sync status badge (Saving / Saved / Error).
 */
import { useAutoSave } from '@QuickDraw/Hooks/useAutoSave';
import { useCallback, useState } from 'react';
import { Tldraw, createTLStore, loadSnapshot } from 'tldraw';
import 'tldraw/tldraw.css';

import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';

const STATUS_CONFIG = {
  idle: null,
  unsaved: { label: 'Unsaved', variant: 'secondary' },
  saving: { label: 'Saving…', variant: 'secondary' },
  saved: { label: 'Saved', variant: 'outline' },
  error: { label: 'Save failed', variant: 'destructive' },
};

export default function Show({ quickdraw, documentState }) {
  const [editor, setEditor] = useState(null);

  const [store] = useState(() => {
    const s = createTLStore();
    if (documentState) {
      try {
        const parsed =
          typeof documentState === 'string' ? JSON.parse(documentState) : documentState;
        if (parsed?.document) {
          loadSnapshot(s, { document: parsed.document });
        }
      } catch (err) {
        console.warn('[QuickDraw] Failed to load snapshot, starting fresh:', err);
      }
    }
    return s;
  });

  const { saveStatus } = useAutoSave(editor, quickdraw.id);

  const handleMount = useCallback((editorInstance) => {
    setEditor(editorInstance);
  }, []);

  const statusConfig = STATUS_CONFIG[saveStatus];

  return (
    <PageShell
      title={quickdraw.name}
      description={quickdraw.description}
      backRoute={route('quickdraw.index')}
      actions={
        statusConfig && (
          <Badge variant={statusConfig.variant} className="text-xs font-normal">
            {statusConfig.label}
          </Badge>
        )
      }
      className="!gap-4"
    >
      <div
        className="relative rounded-lg overflow-hidden border"
        style={{ height: 'calc(100svh - 16rem)' }}
      >
        <Tldraw store={store} onMount={handleMount} />
      </div>
    </PageShell>
  );
}
