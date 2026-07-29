/**
 * NucleusTableView
 *
 * Interactive table with breadcrumb drill-down navigation.
 * Click any nested object or array cell to explore it.
 * Navigate back via the breadcrumb trail at the top.
 *
 * Handles:
 *  - Array of objects → sortable column headers
 *  - Array of primitives → single value column
 *  - Plain object → key / value rows (any nested cell is clickable)
 */
import { ArrowUpDown, ChevronDown, ChevronRight, ChevronUp } from 'lucide-react';
import { useMemo, useState } from 'react';

// ── Shared palette (mirrors Monaco vs-dark) ───────────────────────────────────
const EDITOR_FONT = "'JetBrains Mono', 'Fira Code', 'Cascadia Code', Menlo, monospace";
const FONT_SIZE = 13;

const C = {
  string: '#ce9178',
  number: '#b5cea8',
  boolean: '#569cd6',
  null: '#569cd6',
  key: '#9cdcfe',
  link: '#4ec9b0', // teal — clickable nested values
  linkHover: '#6ddec8',
  header: 'rgba(255,255,255,0.50)',
  border: 'rgba(255,255,255,0.07)',
  rowHover: 'rgba(255,255,255,0.04)',
  dim: 'rgba(255,255,255,0.35)',
  text: 'rgba(255,255,255,0.82)',
  bg: '#1e1e1e',
  headerBg: '#252525', // solid — transparent breaks sticky header
  sortActive: '#569cd6',
  breadcrumbBg: 'rgba(255,255,255,0.04)',
  breadcrumbActive: 'rgba(255,255,255,0.80)',
  breadcrumbInactive: 'rgba(255,255,255,0.35)',
};

// ── Inline value renderer ─────────────────────────────────────────────────────
// onDrillDown is provided for clickable nested nodes.
function CellValue({ value, onDrillDown }) {
  const isNested =
    (Array.isArray(value) || (typeof value === 'object' && value !== null)) && onDrillDown;

  if (value === null || value === undefined) return <span style={{ color: C.null }}>null</span>;

  if (typeof value === 'string')
    return (
      <span style={{ color: C.string }} title={value}>
        &quot;{value.length > 60 ? value.slice(0, 60) + '…' : value}&quot;
      </span>
    );

  if (typeof value === 'number') return <span style={{ color: C.number }}>{value}</span>;

  if (typeof value === 'boolean') return <span style={{ color: C.boolean }}>{String(value)}</span>;

  if (Array.isArray(value)) {
    const label = `[ ${value.length} item${value.length !== 1 ? 's' : ''} ]`;
    return isNested ? (
      <button
        onClick={onDrillDown}
        style={{
          color: C.link,
          background: 'none',
          border: 'none',
          cursor: 'pointer',
          fontFamily: EDITOR_FONT,
          fontSize: FONT_SIZE,
          padding: 0,
          textDecoration: 'underline',
          textDecorationStyle: 'dotted',
          textUnderlineOffset: 3,
        }}
        onMouseEnter={(e) => (e.currentTarget.style.color = C.linkHover)}
        onMouseLeave={(e) => (e.currentTarget.style.color = C.link)}
        title="Click to explore"
      >
        {label}
      </button>
    ) : (
      <span style={{ color: C.dim }}>{label}</span>
    );
  }

  if (typeof value === 'object') {
    const label = `{ ${Object.keys(value).length} key${Object.keys(value).length !== 1 ? 's' : ''} }`;
    return isNested ? (
      <button
        onClick={onDrillDown}
        style={{
          color: C.link,
          background: 'none',
          border: 'none',
          cursor: 'pointer',
          fontFamily: EDITOR_FONT,
          fontSize: FONT_SIZE,
          padding: 0,
          textDecoration: 'underline',
          textDecorationStyle: 'dotted',
          textUnderlineOffset: 3,
        }}
        onMouseEnter={(e) => (e.currentTarget.style.color = C.linkHover)}
        onMouseLeave={(e) => (e.currentTarget.style.color = C.link)}
        title="Click to explore"
      >
        {label}
      </button>
    ) : (
      <span style={{ color: C.dim }}>{label}</span>
    );
  }

  return <span style={{ color: C.text }}>{String(value)}</span>;
}

// ── Sortable object-array table ───────────────────────────────────────────────
function ObjectArrayTable({ data, onDrillDown }) {
  const [sortKey, setSortKey] = useState(null);
  const [sortDir, setSortDir] = useState('asc');
  const [colFilters, setColFilters] = useState({});

  const columns = useMemo(() => {
    const keys = new Set();
    data.forEach((row) => {
      if (row && typeof row === 'object' && !Array.isArray(row)) {
        Object.keys(row).forEach((k) => keys.add(k));
      }
    });
    return [...keys];
  }, [data]);

  const rows = useMemo(() => {
    let result = data;
    // Apply column filters
    Object.entries(colFilters).forEach(([col, term]) => {
      if (!term) return;
      const t = term.toLowerCase();
      result = result.filter((row) => {
        const v = row?.[col];
        if (v === null || v === undefined) return false;
        return String(v).toLowerCase().includes(t);
      });
    });
    // Apply sort
    if (sortKey) {
      result = [...result].sort((a, b) => {
        const av = a?.[sortKey] ?? '';
        const bv = b?.[sortKey] ?? '';
        const cmp = String(av).localeCompare(String(bv), undefined, { numeric: true });
        return sortDir === 'asc' ? cmp : -cmp;
      });
    }
    return result;
  }, [data, sortKey, sortDir, colFilters]);

  const toggleSort = (col) => {
    if (sortKey === col) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else {
      setSortKey(col);
      setSortDir('asc');
    }
  };

  return (
    <table
      style={{
        width: '100%',
        minWidth: 'max-content',
        borderCollapse: 'collapse',
        fontFamily: EDITOR_FONT,
        fontSize: FONT_SIZE,
      }}
    >
      <thead style={{ position: 'sticky', top: 0, zIndex: 2, background: C.headerBg }}>
        {/* Sort header row */}
        <tr>
          <th style={thStyle({ dim: true })}>#</th>
          {columns.map((col) => (
            <th
              key={col}
              onClick={() => toggleSort(col)}
              style={thStyle({ active: sortKey === col })}
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
                  <ArrowUpDown style={{ width: 10, height: 10, opacity: 0.35 }} />
                )}
              </span>
            </th>
          ))}
        </tr>
        {/* Per-column filter input row */}
        <tr style={{ background: C.headerBg }}>
          <td style={tdDimStyle()} />
          {columns.map((col) => (
            <td key={col} style={{ padding: '3px 8px', borderBottom: `1px solid ${C.border}` }}>
              <input
                type="text"
                value={colFilters[col] ?? ''}
                onChange={(e) => setColFilters((prev) => ({ ...prev, [col]: e.target.value }))}
                placeholder="filter…"
                style={{
                  width: '100%',
                  background: 'rgba(255,255,255,0.06)',
                  border: `1px solid ${colFilters[col] ? C.sortActive : 'rgba(255,255,255,0.1)'}`,
                  borderRadius: 3,
                  color: 'rgba(255,255,255,0.8)',
                  fontFamily: EDITOR_FONT,
                  fontSize: 11,
                  padding: '2px 6px',
                  outline: 'none',
                }}
              />
            </td>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((row, idx) => (
          <tr
            key={idx}
            style={{ borderBottom: `1px solid ${C.border}` }}
            onMouseEnter={(e) => (e.currentTarget.style.background = C.rowHover)}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <td style={tdDimStyle()}>{idx + 1}</td>
            {columns.map((col) => (
              <td key={col} style={tdStyle()}>
                <CellValue
                  value={row?.[col]}
                  onDrillDown={() => onDrillDown([idx, col], row?.[col])}
                />
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}

/** Single-column primitive array */
function PrimitiveArrayTable({ data }) {
  return (
    <table
      style={{
        width: '100%',
        minWidth: 'max-content',
        borderCollapse: 'collapse',
        fontFamily: EDITOR_FONT,
        fontSize: FONT_SIZE,
      }}
    >
      <thead style={{ position: 'sticky', top: 0, zIndex: 2, background: C.headerBg }}>
        <tr>
          <th style={thStyle({ dim: true })}>#</th>
          <th style={thStyle({})}>value</th>
        </tr>
      </thead>
      <tbody>
        {data.map((item, idx) => (
          <tr
            key={idx}
            style={{ borderBottom: `1px solid ${C.border}` }}
            onMouseEnter={(e) => (e.currentTarget.style.background = C.rowHover)}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <td style={tdDimStyle()}>{idx + 1}</td>
            <td style={tdStyle()}>
              <CellValue value={item} />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

/** Key/value table for plain objects — nested cells are clickable */
function ObjectKeyValueTable({ data, onDrillDown }) {
  return (
    <table
      style={{
        width: '100%',
        minWidth: 'max-content',
        borderCollapse: 'collapse',
        fontFamily: EDITOR_FONT,
        fontSize: FONT_SIZE,
      }}
    >
      <thead style={{ position: 'sticky', top: 0, zIndex: 2, background: C.headerBg }}>
        <tr>
          <th style={{ ...thStyle({}), width: '35%' }}>key</th>
          <th style={thStyle({})}>value</th>
        </tr>
      </thead>
      <tbody>
        {Object.entries(data).map(([k, v]) => (
          <tr
            key={k}
            style={{ borderBottom: `1px solid ${C.border}` }}
            onMouseEnter={(e) => (e.currentTarget.style.background = C.rowHover)}
            onMouseLeave={(e) => (e.currentTarget.style.background = 'transparent')}
          >
            <td style={{ ...tdStyle(), color: C.key, fontWeight: 500 }}>{k}</td>
            <td style={tdStyle()}>
              <CellValue value={v} onDrillDown={() => onDrillDown([k], v)} />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

// ── Style helpers ─────────────────────────────────────────────────────────────
function thStyle({ dim = false, active = false }) {
  return {
    padding: '6px 12px',
    textAlign: dim ? 'right' : 'left',
    color: dim ? C.dim : active ? C.sortActive : C.header,
    fontWeight: 500,
    fontSize: dim ? 11 : FONT_SIZE,
    borderBottom: `1px solid ${C.border}`,
    cursor: dim ? 'default' : 'pointer',
    userSelect: 'none',
    whiteSpace: 'nowrap',
  };
}
function tdStyle() {
  return { padding: '5px 12px', maxWidth: 320, whiteSpace: 'nowrap' };
}
function tdDimStyle() {
  return {
    padding: '5px 12px',
    color: C.dim,
    textAlign: 'right',
    fontSize: 11,
    userSelect: 'none',
    width: 36,
    whiteSpace: 'nowrap',
  };
}

// ── Breadcrumb ────────────────────────────────────────────────────────────────
function Breadcrumb({ path, onNavigate }) {
  return (
    <div
      style={{
        display: 'flex',
        alignItems: 'center',
        gap: 4,
        flexWrap: 'wrap',
        padding: '6px 12px',
        borderBottom: `1px solid ${C.border}`,
        background: C.breadcrumbBg,
        fontFamily: EDITOR_FONT,
        fontSize: 12,
      }}
    >
      <button
        onClick={() => onNavigate([])}
        style={{
          color: path.length === 0 ? C.breadcrumbActive : C.link,
          background: 'none',
          border: 'none',
          cursor: 'pointer',
          padding: 0,
          fontFamily: EDITOR_FONT,
          fontSize: 12,
        }}
      >
        root
      </button>
      {path.map((seg, idx) => (
        <span key={idx} style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
          <ChevronRight style={{ width: 10, height: 10, color: C.dim }} />
          <button
            onClick={() => onNavigate(path.slice(0, idx + 1))}
            style={{
              color: idx === path.length - 1 ? C.breadcrumbActive : C.link,
              background: 'none',
              border: 'none',
              cursor: idx === path.length - 1 ? 'default' : 'pointer',
              padding: 0,
              fontFamily: EDITOR_FONT,
              fontSize: 12,
            }}
          >
            {String(seg)}
          </button>
        </span>
      ))}
    </div>
  );
}

// ── Root component ────────────────────────────────────────────────────────────
export default function NucleusTableView({ json }) {
  const [path, setPath] = useState([]);

  const root = useMemo(() => {
    try {
      return JSON.parse(json);
    } catch (_error) {
      return undefined;
    }
  }, [json]);

  // Reset path when JSON changes
  const current = useMemo(() => {
    if (root === undefined) return undefined;
    let node = root;
    for (const seg of path) {
      if (node === null || node === undefined) return undefined;
      node = node[seg];
    }
    return node;
  }, [root, path]);

  const drillDown = (segments, _value) => {
    setPath((prev) => [...prev, ...segments]);
  };

  const navigate = (newPath) => setPath(newPath);

  const containerStyle = {
    height: '100%',
    minHeight: 0,
    display: 'flex',
    flexDirection: 'column',
    background: C.bg,
    fontFamily: EDITOR_FONT,
  };

  if (root === undefined) {
    return (
      <div
        style={{
          ...containerStyle,
          alignItems: 'center',
          justifyContent: 'center',
          color: C.dim,
          fontSize: 13,
        }}
      >
        Invalid JSON — fix errors in the editor to see the table view.
      </div>
    );
  }

  const isArray = Array.isArray(current);
  const isObject = !isArray && typeof current === 'object' && current !== null;

  if (!isArray && !isObject) {
    return (
      <div style={containerStyle}>
        {path.length > 0 && <Breadcrumb path={path} onNavigate={navigate} />}
        <div
          style={{
            flex: 1,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: C.dim,
            fontSize: 13,
          }}
        >
          <span style={{ color: C.string }}>&quot;{String(current)}&quot;</span>
        </div>
      </div>
    );
  }

  if (isArray && current.length === 0) {
    return (
      <div style={containerStyle}>
        {path.length > 0 && <Breadcrumb path={path} onNavigate={navigate} />}
        <div
          style={{
            flex: 1,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: C.dim,
            fontSize: 13,
          }}
        >
          Empty array.
        </div>
      </div>
    );
  }

  const firstItem = isArray ? current[0] : null;
  const isObjectArray =
    isArray && firstItem !== null && typeof firstItem === 'object' && !Array.isArray(firstItem);

  return (
    <div style={containerStyle}>
      <Breadcrumb path={path} onNavigate={navigate} />
      <div style={{ flex: 1, minHeight: 0, overflowY: 'auto', overflowX: 'auto' }}>
        {isObjectArray && <ObjectArrayTable data={current} onDrillDown={drillDown} />}
        {isArray && !isObjectArray && <PrimitiveArrayTable data={current} />}
        {isObject && <ObjectKeyValueTable data={current} onDrillDown={drillDown} />}
      </div>
    </div>
  );
}
