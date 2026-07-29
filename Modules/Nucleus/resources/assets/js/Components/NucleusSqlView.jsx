/**
 * NucleusSqlView
 *
 * SQL query panel powered by AlaSQL.
 * Parses the current editor JSON and registers array-valued keys as SQL tables.
 * A Monaco SQL editor (top) lets the user write queries; results render in
 * a sortable table (bottom). Ctrl+Enter runs the query.
 *
 * Table registration:
 *   { users: [...], orders: [...] }  →  FROM users, FROM orders
 *   [...]                            →  FROM data
 */
import Editor from '@monaco-editor/react';
import alasql from 'alasql';
import {
  ArrowDownAz,
  ArrowUpDown,
  ChevronDown,
  ChevronUp,
  Clock,
  Database,
  Play,
  RotateCcw,
  Trash2,
} from 'lucide-react';
import { Fragment, useCallback, useMemo, useRef, useState } from 'react';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

// ── Shared palette ────────────────────────────────────────────────────────────
const EDITOR_FONT = "'JetBrains Mono', 'Fira Code', 'Cascadia Code', Menlo, monospace";
const FS = 13;

const C = {
  bg: '#1e1e1e',
  headerBg: '#252525',
  border: 'rgba(255,255,255,0.07)',
  rowHover: 'rgba(255,255,255,0.04)',
  dim: 'rgba(255,255,255,0.35)',
  text: 'rgba(255,255,255,0.82)',
  key: '#9cdcfe',
  string: '#ce9178',
  number: '#b5cea8',
  boolean: '#569cd6',
  null: '#569cd6',
  sortActive: '#569cd6',
  error: '#f97583',
  success: '#4ec9b0',
};

// ── Cell value renderer ─────────────────────────────────────────────────────
function Cell({ value, onClick }) {
  if (value === null || value === undefined) return <span style={{ color: C.null }}>null</span>;
  if (typeof value === 'string')
    return (
      <span style={{ color: C.string }} title={value}>
        &quot;{value.length > 80 ? value.slice(0, 80) + '…' : value}&quot;
      </span>
    );
  if (typeof value === 'number') return <span style={{ color: C.number }}>{value}</span>;
  if (typeof value === 'boolean') return <span style={{ color: C.boolean }}>{String(value)}</span>;
  if (typeof value === 'object') {
    const label = Array.isArray(value)
      ? `[ ${value.length} items ]`
      : `{ ${Object.keys(value).length} keys }`;
    return (
      <button
        onClick={onClick}
        style={{
          background: 'rgba(255,255,255,0.06)',
          border: `1px solid rgba(255,255,255,0.12)`,
          borderRadius: 3,
          color: C.dim,
          fontFamily: EDITOR_FONT,
          fontSize: 11,
          padding: '1px 6px',
          cursor: 'pointer',
          display: 'inline-flex',
          alignItems: 'center',
          gap: 4,
        }}
        onMouseEnter={(e) => (e.currentTarget.style.background = 'rgba(255,255,255,0.12)')}
        onMouseLeave={(e) => (e.currentTarget.style.background = 'rgba(255,255,255,0.06)')}
        title="Click to expand"
      >
        {label}
        <span style={{ opacity: 0.5, fontSize: 10 }}>▾</span>
      </button>
    );
  }
  return <span style={{ color: C.text }}>{String(value)}</span>;
}

// ── Results table ────────────────────────────────────────────────────────────
function ResultsTable({ rows }) {
  const [sortKey, setSortKey] = useState(null);
  const [sortDir, setSortDir] = useState('asc');
  const [expanded, setExpanded] = useState(null); // { rowIdx, col }

  const columns = useMemo(() => {
    const keys = new Set();
    rows.forEach((r) => Object.keys(r).forEach((k) => keys.add(k)));
    return [...keys];
  }, [rows]);

  const sorted = useMemo(() => {
    if (!sortKey) return rows;
    return [...rows].sort((a, b) => {
      const cmp = String(a[sortKey] ?? '').localeCompare(String(b[sortKey] ?? ''), undefined, {
        numeric: true,
      });
      return sortDir === 'asc' ? cmp : -cmp;
    });
  }, [rows, sortKey, sortDir]);

  const toggle = (col) => {
    if (sortKey === col) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else {
      setSortKey(col);
      setSortDir('asc');
    }
  };

  const toggleExpand = (rowIdx, col, value) => {
    if (value === null || typeof value !== 'object') return;
    setExpanded((prev) =>
      prev?.rowIdx === rowIdx && prev?.col === col ? null : { rowIdx, col, value }
    );
  };

  return (
    <table
      style={{
        width: '100%',
        minWidth: 'max-content',
        borderCollapse: 'collapse',
        fontFamily: EDITOR_FONT,
        fontSize: FS,
      }}
    >
      <thead style={{ position: 'sticky', top: 0, zIndex: 2, background: C.headerBg }}>
        <tr>
          <th
            style={{
              padding: '5px 10px',
              textAlign: 'right',
              color: C.dim,
              fontSize: 11,
              borderBottom: `1px solid ${C.border}`,
              userSelect: 'none',
              width: 36,
            }}
          >
            #
          </th>
          {columns.map((col) => (
            <th
              key={col}
              onClick={() => toggle(col)}
              style={{
                padding: '5px 12px',
                textAlign: 'left',
                color: sortKey === col ? C.sortActive : 'rgba(255,255,255,0.5)',
                fontWeight: 500,
                borderBottom: `1px solid ${C.border}`,
                cursor: 'pointer',
                userSelect: 'none',
                whiteSpace: 'nowrap',
              }}
            >
              <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                {col}
                {sortKey === col ? (
                  sortDir === 'asc' ? (
                    <ChevronUp style={{ width: 10, height: 10, color: C.sortActive }} />
                  ) : (
                    <ChevronDown style={{ width: 10, height: 10, color: C.sortActive }} />
                  )
                ) : (
                  <ArrowUpDown style={{ width: 10, height: 10, opacity: 0.3 }} />
                )}
              </span>
            </th>
          ))}
        </tr>
      </thead>
      <tbody>
        {sorted.map((row, idx) => (
          <Fragment key={idx}>
            <tr
              key={`row-${idx}`}
              style={{ borderBottom: `1px solid ${C.border}` }}
              onMouseEnter={(e) => (e.currentTarget.style.background = C.rowHover)}
              onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
            >
              <td
                style={{
                  padding: '4px 10px',
                  color: C.dim,
                  textAlign: 'right',
                  fontSize: 11,
                  userSelect: 'none',
                }}
              >
                {idx + 1}
              </td>
              {columns.map((col) => (
                <td key={col} style={{ padding: '4px 12px', whiteSpace: 'nowrap' }}>
                  <Cell value={row[col]} onClick={() => toggleExpand(idx, col, row[col])} />
                </td>
              ))}
            </tr>
            {/* Expanded detail row */}
            {expanded?.rowIdx === idx && (
              <tr key={`expand-${idx}`}>
                <td />
                <td
                  colSpan={columns.length}
                  style={{
                    padding: 0,
                    borderBottom: `1px solid ${C.border}`,
                  }}
                >
                  <div
                    style={{
                      padding: '10px 14px',
                      background: 'rgba(255,255,255,0.03)',
                      borderLeft: `2px solid ${C.sortActive}`,
                    }}
                  >
                    <div
                      style={{
                        fontSize: 10,
                        color: C.dim,
                        marginBottom: 6,
                        textTransform: 'uppercase',
                        letterSpacing: '0.06em',
                      }}
                    >
                      {expanded.col}
                    </div>
                    <div style={{ height: 220, borderRadius: 4, overflow: 'hidden' }}>
                      <Editor
                        height="100%"
                        language="json"
                        theme="vs-dark"
                        value={JSON.stringify(expanded.value, null, 2)}
                        options={{
                          readOnly: true,
                          fontSize: 14,
                          fontFamily: EDITOR_FONT,
                          minimap: { enabled: false },
                          lineNumbers: 'off',
                          scrollBeyondLastLine: false,
                          folding: true,
                          wordWrap: 'on',
                          padding: { top: 8, bottom: 8 },
                          glyphMargin: false,
                          overviewRulerBorder: false,
                          renderLineHighlight: 'none',
                          scrollbar: { verticalScrollbarSize: 4, horizontalScrollbarSize: 4 },
                        }}
                      />
                    </div>
                  </div>
                </td>
              </tr>
            )}
          </Fragment>
        ))}
      </tbody>
    </table>
  );
}

// ── Main component ────────────────────────────────────────────────────────────
const DEFAULT_QUERY = 'SELECT * FROM data LIMIT 10';

const HISTORY_KEY = 'nucleus_sql_history';
const HISTORY_MAX = 50;

function loadHistory() {
  try {
    return JSON.parse(localStorage.getItem(HISTORY_KEY) ?? '[]');
  } catch {
    return [];
  }
}

function pushHistory(sql) {
  const trimmed = sql.trim();
  if (!trimmed) return;
  const prev = loadHistory().filter((h) => h.sql !== trimmed);
  const next = [{ sql: trimmed, ts: Date.now() }, ...prev].slice(0, HISTORY_MAX);
  localStorage.setItem(HISTORY_KEY, JSON.stringify(next));
}

export default function NucleusSqlView({ json }) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState(null);
  const [error, setError] = useState(null);
  const [meta, setMeta] = useState(null); // { rows, elapsed }
  const [history, setHistory] = useState(loadHistory);
  const histIdxRef = useRef(-1); // -1 = not browsing
  const runRef = useRef(null); // stable ref for the Ctrl+Enter shortcut

  // Derive available tables from JSON ─────────────────────────────────────────
  const { tables, defaultQuery } = useMemo(() => {
    try {
      const parsed = JSON.parse(json);
      if (Array.isArray(parsed)) {
        return {
          tables: [{ name: 'data', rows: parsed.length }],
          defaultQuery: 'SELECT * FROM data LIMIT 10',
        };
      }
      if (parsed !== null && typeof parsed === 'object') {
        const allKeys = Object.entries(parsed)
          .filter(([, v]) => v !== null && typeof v === 'object')
          .map(([k, v]) => ({
            name: k,
            rows: Array.isArray(v) ? v.length : null, // null = single object
          }));
        const first = allKeys[0]?.name ?? 'data';
        return {
          tables: allKeys,
          defaultQuery: `SELECT * FROM ${first} LIMIT 10`,
        };
      }
    } catch (_e) {
      // invalid JSON — no tables
    }
    return { tables: [], defaultQuery: DEFAULT_QUERY };
  }, [json]);

  // Init query when tables change ──────────────────────────────────────────────
  const initialised = useRef(false);
  if (!initialised.current && defaultQuery) {
    setQuery(defaultQuery);
    initialised.current = true;
  }

  // Execute query ──────────────────────────────────────────────────────────────
  const runQuery = useCallback(
    (q) => {
      const sql = (q ?? query).trim();
      if (!sql) return;
      // Clear previous results before running new query
      setResults(null);
      setError(null);
      setMeta(null);
      try {
        const parsed = JSON.parse(json);

        // Register tables in AlaSQL
        // Primitive arrays (strings/numbers) are wrapped as {value:item}
        // so AlaSQL doesn't spread characters into numbered columns.
        const normalise = (arr) => {
          if (!arr.length) return arr;
          const first = arr[0];
          if (first === null || typeof first !== 'object') {
            return arr.map((item) => ({ value: item }));
          }
          return arr;
        };

        if (Array.isArray(parsed)) {
          alasql.tables['data'] = { data: normalise(parsed) };
        } else if (parsed !== null && typeof parsed === 'object') {
          // Clear stale tables first
          Object.keys(alasql.tables).forEach((t) => {
            delete alasql.tables[t];
          });
          Object.entries(parsed).forEach(([k, v]) => {
            if (v === null || typeof v !== 'object') return;
            if (Array.isArray(v)) {
              alasql.tables[k] = { data: normalise(v) };
            } else {
              // Plain object → single-row table
              alasql.tables[k] = { data: [v] };
            }
          });
        }

        const t0 = Date.now();
        const result = alasql(sql);
        const elapsed = Date.now() - t0;

        if (Array.isArray(result)) {
          setResults(result);
          setMeta({ rows: result.length, elapsed });
          setError(null);
        } else {
          // Non-SELECT (e.g. aggregates may return a scalar)
          setResults([{ result }]);
          setMeta({ rows: 1, elapsed });
          setError(null);
        }
        // Persist to history
        pushHistory(sql);
        setHistory(loadHistory());
        histIdxRef.current = -1;
      } catch (err) {
        setError(err.message);
        setResults(null);
        setMeta(null);
      }
    },
    [json, query]
  );

  // Keep ref in sync so the Ctrl+Enter binding always calls the latest version
  runRef.current = runQuery;

  const handleEditorMount = useCallback((editor, monaco) => {
    // Ctrl+Enter → run
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, () => {
      runRef.current();
    });
    // Ctrl+ArrowUp → older history entry
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.UpArrow, () => {
      const h = loadHistory();
      if (!h.length) return;
      const next = Math.min(histIdxRef.current + 1, h.length - 1);
      histIdxRef.current = next;
      setQuery(h[next].sql);
    });
    // Ctrl+ArrowDown → newer history entry
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.DownArrow, () => {
      const h = loadHistory();
      const next = histIdxRef.current - 1;
      if (next < 0) {
        histIdxRef.current = -1;
        return;
      }
      histIdxRef.current = next;
      setQuery(h[next].sql);
    });
  }, []);

  // Chip click → insert table name into query bar ─────────────────────────────
  const insertTable = (name) => {
    setQuery(`SELECT * FROM ${name} LIMIT 10`);
    initialised.current = true;
  };

  return (
    <div
      style={{
        height: '100%',
        minHeight: 0,
        display: 'flex',
        flexDirection: 'column',
        background: C.bg,
        fontFamily: EDITOR_FONT,
      }}
    >
      {/* ── Header: available tables + run button ── */}
      <div
        style={{
          flexShrink: 0,
          display: 'flex',
          alignItems: 'center',
          gap: 8,
          padding: '6px 12px',
          borderBottom: `1px solid ${C.border}`,
          background: C.headerBg,
          flexWrap: 'wrap',
        }}
      >
        <Database style={{ width: 13, height: 13, color: C.dim, flexShrink: 0 }} />
        <span style={{ fontSize: 11, color: C.dim, flexShrink: 0 }}>Tables:</span>

        {tables.length === 0 ? (
          <span style={{ fontSize: 11, color: C.error }}>
            No array-valued keys found — paste a JSON array or object with array properties.
          </span>
        ) : (
          tables.map(({ name, rows }) => (
            <button
              key={name}
              onClick={() => insertTable(name)}
              style={{
                background: 'rgba(255,255,255,0.07)',
                border: `1px solid ${C.border}`,
                borderRadius: 4,
                color: C.key,
                fontFamily: EDITOR_FONT,
                fontSize: 11,
                padding: '1px 8px',
                cursor: 'pointer',
                display: 'inline-flex',
                alignItems: 'center',
                gap: 4,
              }}
              onMouseEnter={(e) => (e.currentTarget.style.background = 'rgba(255,255,255,0.12)')}
              onMouseLeave={(e) => (e.currentTarget.style.background = 'rgba(255,255,255,0.07)')}
              title={`Click to query ${name}`}
            >
              {name}
              <span style={{ color: C.dim, fontSize: 10 }}>
                {rows === null ? '{}' : `(${rows})`}
              </span>
            </button>
          ))
        )}

        <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: 6 }}>
          {meta && (
            <span style={{ fontSize: 11, color: C.success }}>
              {meta.rows} row{meta.rows !== 1 ? 's' : ''} · {meta.elapsed}ms
            </span>
          )}

          {/* History dropdown */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="sm"
                className="h-7 gap-1.5 px-2 text-xs text-muted-foreground hover:text-foreground"
                title="Query history"
              >
                <Clock style={{ width: 11, height: 11 }} />
                {history.length > 0 && (
                  <span style={{ fontSize: 10, opacity: 0.6 }}>{history.length}</span>
                )}
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 max-h-64 overflow-y-auto">
              <DropdownMenuLabel className="flex items-center justify-between py-1.5">
                <span>Query history</span>
                {history.length > 0 && (
                  <button
                    onClick={() => {
                      localStorage.removeItem(HISTORY_KEY);
                      setHistory([]);
                    }}
                    className="flex items-center gap-1 text-[10px] text-muted-foreground hover:text-destructive"
                  >
                    <Trash2 style={{ width: 10, height: 10 }} />
                    Clear
                  </button>
                )}
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              {history.length === 0 ? (
                <div className="px-3 py-4 text-center text-xs text-muted-foreground">
                  No queries yet.
                </div>
              ) : (
                history.map((entry, i) => (
                  <DropdownMenuItem
                    key={i}
                    onClick={() => {
                      setQuery(entry.sql);
                      histIdxRef.current = i;
                    }}
                    className="flex flex-col items-start gap-0.5 py-2 cursor-pointer"
                  >
                    <span className="w-full truncate font-mono text-[11px]">
                      {entry.sql.length > 60 ? entry.sql.slice(0, 60) + '…' : entry.sql}
                    </span>
                    <span className="text-[10px] text-muted-foreground">
                      {new Date(entry.ts).toLocaleString()}
                    </span>
                  </DropdownMenuItem>
                ))
              )}
            </DropdownMenuContent>
          </DropdownMenu>

          <Button
            size="sm"
            onClick={() => runQuery()}
            className="h-7 gap-1.5 px-3 text-xs"
            title="Run query (Ctrl+Enter)"
          >
            <Play style={{ width: 11, height: 11 }} />
            Run
          </Button>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => {
              setResults(null);
              setError(null);
              setMeta(null);
              setQuery(defaultQuery);
              initialised.current = false;
            }}
            className="h-7 px-2 text-xs text-muted-foreground hover:text-foreground"
            title="Reset"
          >
            <RotateCcw style={{ width: 11, height: 11 }} />
          </Button>
        </div>
      </div>

      {/* ── SQL Editor ── */}
      <div style={{ flexShrink: 0, height: 180, borderBottom: `1px solid ${C.border}` }}>
        <Editor
          height="100%"
          language="sql"
          theme="vs-dark"
          value={query}
          onChange={(v) => setQuery(v ?? '')}
          onMount={handleEditorMount}
          options={{
            fontSize: 14,
            fontFamily: EDITOR_FONT,
            minimap: { enabled: false },
            wordWrap: 'on',
            lineNumbers: 'on',
            scrollBeyondLastLine: false,
            smoothScrolling: true,
            padding: { top: 12, bottom: 12 },
            tabSize: 2,
            glyphMargin: false,
            overviewRulerBorder: false,
            scrollbar: { verticalScrollbarSize: 6, horizontalScrollbarSize: 6 },
          }}
        />
      </div>

      {/* ── Results ── */}
      <div
        style={{
          flex: 1,
          minHeight: 0,
          overflowY: 'auto',
          overflowX: 'auto',
        }}
      >
        {!results && !error && (
          <div
            style={{
              height: '100%',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              gap: 8,
              color: C.dim,
              fontSize: 13,
            }}
          >
            <ArrowDownAz style={{ width: 28, height: 28, opacity: 0.3 }} />
            <span>Write a SQL query above and press</span>
            <kbd
              style={{
                background: 'rgba(255,255,255,0.08)',
                border: `1px solid ${C.border}`,
                borderRadius: 4,
                padding: '2px 8px',
                fontSize: 11,
                fontFamily: EDITOR_FONT,
                color: 'rgba(255,255,255,0.5)',
              }}
            >
              Ctrl+Enter
            </kbd>
          </div>
        )}

        {error && !results && (
          <div
            style={{
              padding: 20,
              color: C.error,
              fontFamily: EDITOR_FONT,
              fontSize: 13,
              whiteSpace: 'pre-wrap',
            }}
          >
            {error}
          </div>
        )}

        {results && results.length === 0 && (
          <div
            style={{
              height: '100%',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: C.dim,
              fontSize: 13,
            }}
          >
            Query returned 0 rows.
          </div>
        )}

        {results && results.length > 0 && <ResultsTable rows={results} />}
      </div>
    </div>
  );
}
