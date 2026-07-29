/**
 * Nucleus Index Page
 *
 * Local-first advanced JSON editor, viewer, and formatter powered by Monaco Editor.
 * Provides a VS Code-like experience with Tree View, JSONPath querying, snippet
 * history, and real-time JSON validation — all processed entirely on the client.
 *
 * @important Import Editor directly (not lazily) from @monaco-editor/react.
 *            The library handles its own async loading internally. Lazy-wrapping
 *            it triggers a React context conflict within the Inertia lifecycle.
 */
import NucleusSnippetPanel from '@Nucleus/Components/NucleusSnippetPanel';
import NucleusSqlView from '@Nucleus/Components/NucleusSqlView';
import NucleusStatusBar from '@Nucleus/Components/NucleusStatusBar';
import NucleusTableView from '@Nucleus/Components/NucleusTableView';
import NucleusToolbar from '@Nucleus/Components/NucleusToolbar';
import NucleusTreeView from '@Nucleus/Components/NucleusTreeView';
import { usePage } from '@inertiajs/react';
import Editor from '@monaco-editor/react';
import axios from 'axios';
import { Braces, Database, Filter, GitBranch, History, Table, X } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import { toast } from 'sonner';

import PageShell from '@/Components/Layouts/PageShell';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/Components/ui/tabs';

// ─── JSONPath query engine ────────────────────────────────────────────────────
// Lightweight client-side implementation — no external dependency. Supports
// simple dot-notation and bracket notation paths (e.g. $.users[0].name).
function evaluateJsonPath(data, path) {
  if (!path || path === '$' || path === '') return data;

  try {
    const cleaned = path.replace(/^\$\.?/, '');
    const segments = cleaned
      .replace(/\[(\d+)\]/g, '.$1')
      .split('.')
      .filter(Boolean);

    let current = data;
    for (const segment of segments) {
      if (current === null || current === undefined) return undefined;
      current = current[segment];
    }
    return current;
  } catch (_error) {
    return undefined;
  }
}

const SAMPLE_JSON = JSON.stringify(
  {
    version: '1.0.0',
    config: {
      theme: 'vs-dark',
      wordWrap: true,
      fontSize: 14,
    },
    users: [
      { id: 1, name: 'Alice', role: 'admin' },
      { id: 2, name: 'Bob', role: 'viewer' },
    ],
    tags: ['json', 'editor', 'nucleus'],
  },
  null,
  2
);

// ─── Main Page ────────────────────────────────────────────────────────────────
export default function Index() {
  const { editorSettings, snippets: initialSnippets } = usePage().props;

  const editorRef = useRef(null);
  const [editorValue, setEditorValue] = useState(SAMPLE_JSON);
  const [jsonError, setJsonError] = useState(null);
  const [isValidJson, setIsValidJson] = useState(true);
  const [isEmptyJson, setIsEmptyJson] = useState(false);
  const [rightPanel, setRightPanel] = useState('tree'); // 'tree' | 'table' | 'history'
  const [jsonPathQuery, setJsonPathQuery] = useState('');
  const [queryResult, setQueryResult] = useState(null);
  const [queryError, setQueryError] = useState(null);
  const [snippets, setSnippets] = useState(initialSnippets ?? []);
  const [isSaving, setIsSaving] = useState(false);
  const [cursor, setCursor] = useState(null);
  const [typeFilter, setTypeFilter] = useState(null); // 'string'|'number'|'boolean'|'null'|'array'|'object'

  // ── Editor lifecycle ────────────────────────────────────────────────────────
  const handleEditorMount = useCallback((editor) => {
    editorRef.current = editor;
    editor.onDidChangeCursorPosition((e) => {
      setCursor({ lineNumber: e.position.lineNumber, column: e.position.column });
    });
  }, []);

  const handleEditorChange = useCallback((value) => {
    const content = value ?? '';
    setEditorValue(content);

    if (!content.trim()) {
      setIsEmptyJson(true);
      setIsValidJson(false);
      setJsonError(null);
      return;
    }

    setIsEmptyJson(false);
    try {
      JSON.parse(content);
      setIsValidJson(true);
      setJsonError(null);
    } catch (err) {
      setIsValidJson(false);
      setJsonError(err.message);
    }
  }, []);

  // ── Toolbar actions ─────────────────────────────────────────────────────────
  const handlePrettify = useCallback(
    (returnValue = false) => {
      try {
        const parsed = JSON.parse(editorValue);
        const pretty = JSON.stringify(parsed, null, 2);
        if (returnValue) return pretty;
        editorRef.current?.setValue(pretty);
        setEditorValue(pretty);
      } catch (_error) {
        return null;
      }
    },
    [editorValue]
  );

  const handleMinify = useCallback(() => {
    try {
      const parsed = JSON.parse(editorValue);
      const minified = JSON.stringify(parsed);
      editorRef.current?.setValue(minified);
      setEditorValue(minified);
    } catch (_error) {
      // parse failed — editor content is invalid JSON, silently skip
    }
  }, [editorValue]);

  const handleClear = useCallback(() => {
    editorRef.current?.setValue('');
    setEditorValue('');
    setIsEmptyJson(true);
    setIsValidJson(false);
    setJsonError(null);
    setJsonPathQuery('');
    setQueryResult(null);
    setQueryError(null);
  }, []);

  // ── Transform operators ──────────────────────────────────────────────────────────
  const transforms = {
    /** Sort all object keys alphabetically, recursively */
    sortKeys(v) {
      if (Array.isArray(v)) return v.map((i) => transforms.sortKeys(i));
      if (v !== null && typeof v === 'object') {
        return Object.fromEntries(
          Object.entries(v)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([k, val]) => [k, transforms.sortKeys(val)])
        );
      }
      return v;
    },
    /** Remove all null / undefined values, recursively */
    removeNulls(v) {
      if (Array.isArray(v)) return v.filter((i) => i != null).map((i) => transforms.removeNulls(i));
      if (v !== null && typeof v === 'object') {
        return Object.fromEntries(
          Object.entries(v)
            .filter(([, val]) => val != null)
            .map(([k, val]) => [k, transforms.removeNulls(val)])
        );
      }
      return v;
    },
    /** Flatten nested objects to dot-notation keys (arrays are kept as-is) */
    flatten(v, prefix = '') {
      if (Array.isArray(v) || v === null || typeof v !== 'object') return { [prefix]: v };
      return Object.entries(v).reduce((acc, [k, val]) => {
        const key = prefix ? `${prefix}.${k}` : k;
        if (val !== null && typeof val === 'object' && !Array.isArray(val)) {
          Object.assign(acc, transforms.flatten(val, key));
        } else {
          acc[key] = val;
        }
        return acc;
      }, {});
    },
    /** Replace every leaf value with its type name */
    schema(v) {
      if (Array.isArray(v)) return v.map((i) => transforms.schema(i));
      if (v === null) return 'null';
      if (typeof v === 'object')
        return Object.fromEntries(Object.entries(v).map(([k, val]) => [k, transforms.schema(val)]));
      return typeof v;
    },
  };

  const handleTransform = useCallback(
    (type) => {
      try {
        const parsed = JSON.parse(editorValue);
        let result;
        if (type === 'flatten') {
          result = transforms.flatten(parsed);
        } else {
          result = transforms[type](parsed);
        }
        const pretty = JSON.stringify(result, null, 2);
        editorRef.current?.setValue(pretty);
        setEditorValue(pretty);
        setIsValidJson(true);
        setJsonError(null);
        toast.success(`Transform applied: ${type}`);
      } catch {
        toast.error('Transform failed — fix JSON errors first.');
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [editorValue]
  );

  // ── Snippet persistence ─────────────────────────────────────────────────────
  const handleSave = useCallback(
    async (title) => {
      setIsSaving(true);
      try {
        const { data } = await axios.post(route('nucleus.snippets.store'), {
          title,
          raw_json: editorValue,
        });
        setSnippets((prev) => [data.snippet, ...prev]);
        toast.success(`"${title}" added to your library.`);
      } catch {
        toast.error('Could not save snippet.');
      } finally {
        setIsSaving(false);
      }
    },
    [editorValue]
  );

  const handleLoadSnippet = useCallback(async (id) => {
    try {
      const { data } = await axios.get(route('nucleus.snippets.show', id));
      const pretty = JSON.stringify(JSON.parse(data.snippet.raw_json), null, 2);
      editorRef.current?.setValue(pretty);
      setEditorValue(pretty);
      setIsValidJson(true);
      setJsonError(null);
      toast.success(`Loaded: ${data.snippet.title}`);
    } catch {
      toast.error('Could not load snippet.');
    }
  }, []);

  const handleDeleteSnippet = useCallback(async (id) => {
    try {
      await axios.delete(route('nucleus.snippets.destroy', id));
      setSnippets((prev) => prev.filter((s) => s.id !== id));
      toast.success('Snippet deleted.');
    } catch {
      toast.error('Could not delete snippet.');
    }
  }, []);

  // ── JSONPath query ──────────────────────────────────────────────────────────
  const handleQueryChange = useCallback(
    (value) => {
      setJsonPathQuery(value);

      if (!value.trim()) {
        setQueryResult(null);
        setQueryError(null);
        return;
      }

      if (!isValidJson) {
        setQueryError('Fix JSON errors before querying.');
        setQueryResult(null);
        return;
      }

      try {
        const parsed = JSON.parse(editorValue);
        const result = evaluateJsonPath(parsed, value.trim());

        if (result === undefined) {
          setQueryError('Path returned no results.');
          setQueryResult(null);
        } else {
          setQueryResult(JSON.stringify(result, null, 2));
          setQueryError(null);
        }
      } catch (err) {
        setQueryError(err.message);
        setQueryResult(null);
      }
    },
    [editorValue, isValidJson]
  );

  // ── Type filter ─────────────────────────────────────────────────────────────
  const filterByType = useCallback((value, type) => {
    if (type === null) return value;
    const check = (v) => {
      if (v === null) return type === 'null';
      if (Array.isArray(v)) return type === 'array';
      return typeof v === type;
    };
    const walk = (v) => {
      if (Array.isArray(v)) {
        const f = v
          .filter((item) => {
            // If this item itself matches the filter type, keep it.
            if (check(item)) return true;
            // If it's a container but doesn't match, recurse to find matching descendants.
            if (item !== null && typeof item === 'object') return walk(item) !== undefined;
            return false;
          })
          .map((item) => {
            // Already matches — keep as-is.
            if (check(item)) return item;
            // Container that doesn't match — return the filtered subtree.
            if (item !== null && typeof item === 'object') return walk(item);
            return item;
          })
          .filter((item) => item !== undefined);
        return f.length ? f : undefined;
      }
      if (v !== null && typeof v === 'object') {
        const result = {};
        for (const [k, child] of Object.entries(v)) {
          // If the child itself matches, keep it directly.
          if (check(child)) {
            result[k] = child;
          } else if (child !== null && typeof child === 'object') {
            // Container that doesn't match — recurse for matching descendants.
            const filtered = walk(child);
            if (filtered !== undefined) result[k] = filtered;
          }
        }
        return Object.keys(result).length ? result : undefined;
      }
      return check(v) ? v : undefined;
    };
    return walk(value);
  }, []);

  const treeContent = (() => {
    const base = queryResult ?? editorValue;
    if (typeFilter === null) return base;
    try {
      const parsed = JSON.parse(base);
      const filtered = filterByType(parsed, typeFilter);
      return filtered !== undefined ? JSON.stringify(filtered, null, 2) : '{}';
    } catch {
      return base;
    }
  })();

  // ── Right panel tab actions ─────────────────────────────────────────────────
  const panelActions = (
    <div className="flex items-center gap-2">
      {!isEmptyJson && (
        <Badge
          variant={isValidJson ? 'outline' : 'destructive'}
          className={`h-5 text-[10px] font-semibold tracking-wide shrink-0 ${
            isValidJson
              ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/10'
              : ''
          }`}
        >
          {isValidJson ? 'VALID JSON' : 'INVALID JSON'}
        </Badge>
      )}

      <div className="overflow-x-auto">
        <Tabs value={rightPanel} onValueChange={setRightPanel}>
          <TabsList className="h-8">
            <TabsTrigger value="tree" className="h-7 gap-1.5 px-3 text-xs">
              <GitBranch className="h-3.5 w-3.5" />
              Tree
            </TabsTrigger>
            <TabsTrigger value="table" className="h-7 gap-1.5 px-3 text-xs">
              <Table className="h-3.5 w-3.5" />
              Table
            </TabsTrigger>
            <TabsTrigger value="sql" className="h-7 gap-1.5 px-3 text-xs">
              <Database className="h-3.5 w-3.5" />
              SQL
            </TabsTrigger>
            <TabsTrigger value="history" className="h-7 gap-1.5 px-3 text-xs">
              <History className="h-3.5 w-3.5" />
              History
              {snippets.length > 0 && (
                <span className="ml-1 rounded bg-muted px-1 py-0.5 text-[9px] font-bold leading-none text-muted-foreground">
                  {snippets.length}
                </span>
              )}
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </div>
    </div>
  );

  return (
    <PageShell
      title="Nucleus"
      description="JSON editor, viewer, and data parser"
      actions={panelActions}
    >
      {/* Editor container — full height minus the PageShell header area */}
      <div
        className="flex flex-col overflow-hidden rounded-lg border border-border bg-card"
        style={{ height: 'calc(100svh - 10rem)' }}
      >
        {/* Toolbar */}
        <NucleusToolbar
          onPrettify={handlePrettify}
          onMinify={handleMinify}
          onClear={handleClear}
          onSave={handleSave}
          onTransform={handleTransform}
          isValidJson={isValidJson}
          isSaving={isSaving}
        />

        {/* JSONPath query bar + Type filter chips */}
        <div className="shrink-0 border-b border-border bg-muted/20 px-4 py-2 space-y-2">
          <div className="flex items-center gap-2">
            <Filter className="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
            <div className="relative flex-1">
              <Input
                value={jsonPathQuery}
                onChange={(e) => handleQueryChange(e.target.value)}
                placeholder="JSONPath query e.g. $.config.theme or $.users[0].name"
                className="h-7 pr-8 font-mono text-xs placeholder:font-sans"
              />
              {jsonPathQuery && (
                <button
                  onClick={() => handleQueryChange('')}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  <X className="h-3.5 w-3.5" />
                </button>
              )}
            </div>
            {queryError && (
              <span className="shrink-0 text-[11px] text-destructive hidden sm:inline">
                {queryError}
              </span>
            )}
            {queryResult && !queryError && (
              <span className="shrink-0 text-[11px] text-emerald-400 hidden sm:inline">
                Result found
              </span>
            )}
          </div>
          {/* JSONPath inline error on mobile */}
          {queryError && <p className="text-[11px] text-destructive sm:hidden">{queryError}</p>}
          {/* Type filter chips */}
          <div className="flex items-center gap-1.5 overflow-x-auto">
            <span className="text-[10px] text-muted-foreground shrink-0">Type:</span>
            {[null, 'string', 'number', 'boolean', 'null', 'array', 'object'].map((t) => (
              <button
                key={t === null ? 'all' : t}
                onClick={() => setTypeFilter(typeFilter === t ? null : t)}
                className={`shrink-0 rounded px-2 py-0.5 text-[10px] font-mono font-medium transition-colors border ${
                  typeFilter === t
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'
                }`}
              >
                {t === null ? 'all' : t}
              </button>
            ))}
          </div>
        </div>

        {/* Main split layout — vertical on mobile, horizontal on md+ */}
        <div className="flex min-h-0 flex-1 flex-col md:flex-row">
          {/* Editor */}
          <div className="h-[45%] min-h-0 md:h-auto md:flex-1">
            <Editor
              height="100%"
              language="json"
              value={editorValue}
              onChange={handleEditorChange}
              onMount={handleEditorMount}
              theme={editorSettings?.theme ?? 'vs-dark'}
              options={{
                fontSize: editorSettings?.fontSize ?? 14,
                fontFamily: "'JetBrains Mono', 'Fira Code', 'Cascadia Code', Menlo, monospace",
                minimap: { enabled: true, scale: 1 },
                wordWrap: editorSettings?.wordWrap ?? 'on',
                lineNumbers: 'on',
                renderLineHighlight: 'line',
                scrollBeyondLastLine: false,
                smoothScrolling: true,
                cursorBlinking: 'smooth',
                formatOnPaste: true,
                automaticLayout: true,
                tabSize: 2,
                padding: { top: 12, bottom: 12 },
                bracketPairColorization: { enabled: true },
                guides: { bracketPairs: true },
                folding: true,
                glyphMargin: false,
                overviewRulerBorder: false,
                scrollbar: {
                  verticalScrollbarSize: 6,
                  horizontalScrollbarSize: 6,
                },
              }}
            />
          </div>

          {/* Right panel */}
          <div className="h-[55%] min-h-0 overflow-hidden border-t border-border bg-[#1e1e1e] md:h-auto md:flex-1 md:border-l md:border-t-0">
            {rightPanel === 'tree' && <NucleusTreeView json={treeContent} />}
            {rightPanel === 'table' && <NucleusTableView json={treeContent} />}
            {rightPanel === 'sql' && <NucleusSqlView json={editorValue} />}
            {rightPanel === 'history' && (
              <NucleusSnippetPanel
                snippets={snippets}
                onLoad={handleLoadSnippet}
                onDelete={handleDeleteSnippet}
              />
            )}
          </div>
        </div>

        {/* Status bar */}
        <NucleusStatusBar
          json={editorValue}
          isValidJson={isValidJson}
          isEmptyJson={isEmptyJson}
          errorMessage={jsonError}
          cursor={cursor}
        />
      </div>
    </PageShell>
  );
}
