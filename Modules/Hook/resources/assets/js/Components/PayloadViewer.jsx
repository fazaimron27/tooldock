/**
 * PayloadViewer Component
 *
 * Renders a collapsible, syntax-highlighted JSON payload viewer using Monaco
 * editor in read-only mode. Falls back to a <pre> block if Monaco hasn't
 * loaded yet.
 *
 * @module Hook/Components/PayloadViewer
 */
import Editor from '@monaco-editor/react';
import { Check, ChevronDown, ChevronRight, Copy } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

import { Button } from '@/Components/ui/button';

/** Lines visible before scrolling (caps Monaco height). */
const MAX_VISIBLE_LINES = 20;
const LINE_HEIGHT = 18; // px — matches Monaco's default

/**
 * Collapsible JSON / text viewer backed by Monaco in read-only mode.
 *
 * @param {Object}                       props
 * @param {string}                       props.label        - Section label
 * @param {Object|Array|string|null}     props.data         - Data to display
 * @param {boolean}                      [props.defaultOpen=false]
 */
export default function PayloadViewer({ label, data, defaultOpen = false }) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  const [copied, setCopied] = useState(false);

  const formatted = useMemo(() => {
    if (!data) return null;
    if (typeof data === 'string') {
      try {
        return JSON.stringify(JSON.parse(data), null, 2);
      } catch {
        return data;
      }
    }
    return JSON.stringify(data, null, 2);
  }, [data]);

  const lineCount = useMemo(() => (formatted ?? '').split('\n').length, [formatted]);
  const editorHeight = Math.min(lineCount, MAX_VISIBLE_LINES) * LINE_HEIGHT + 10;

  const handleCopy = useCallback(async () => {
    if (!formatted) return;
    await navigator.clipboard.writeText(formatted);
    setCopied(true);
    setTimeout(() => setCopied(false), 1500);
  }, [formatted]);

  if (!data) return null;

  return (
    <div className="rounded-md border overflow-hidden">
      {/* ── Header row ── */}
      <div
        role="button"
        tabIndex={0}
        onClick={() => setIsOpen(!isOpen)}
        onKeyDown={(e) => e.key === 'Enter' && setIsOpen(!isOpen)}
        className="flex w-full items-center justify-between px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted/50 transition-colors cursor-pointer"
      >
        <span className="flex items-center gap-1.5">
          {isOpen ? (
            <ChevronDown className="h-3.5 w-3.5" />
          ) : (
            <ChevronRight className="h-3.5 w-3.5" />
          )}
          {label}
        </span>
        {isOpen && (
          <Button
            variant="ghost"
            size="icon"
            className="h-6 w-6"
            onClick={(e) => {
              e.stopPropagation();
              handleCopy();
            }}
          >
            {copied ? <Check className="h-3 w-3 text-green-500" /> : <Copy className="h-3 w-3" />}
          </Button>
        )}
      </div>

      {/* ── Monaco read-only editor ── */}
      {isOpen && (
        <div className="border-t">
          <Editor
            height={editorHeight}
            language="json"
            value={formatted ?? ''}
            theme="vs-dark"
            options={{
              readOnly: true,
              minimap: { enabled: false },
              scrollBeyondLastLine: false,
              wordWrap: 'on',
              fontSize: 12,
              lineNumbers: 'off',
              folding: true,
              renderLineHighlight: 'none',
              overviewRulerLanes: 0,
              hideCursorInOverviewRuler: true,
              scrollbar: {
                vertical: lineCount > MAX_VISIBLE_LINES ? 'auto' : 'hidden',
                horizontal: 'hidden',
                useShadows: false,
              },
              padding: { top: 8, bottom: 8 },
            }}
          />
        </div>
      )}
    </div>
  );
}
