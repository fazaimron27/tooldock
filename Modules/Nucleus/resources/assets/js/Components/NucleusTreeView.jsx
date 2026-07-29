/**
 * NucleusTreeView
 *
 * Recursive interactive tree visualization for JSON data. Uses the exact
 * same font family, font size, and indentation as the Monaco editor so the
 * left and right panels feel visually unified.
 *
 * Indent calculation: Monaco tabSize=2 at 14px JetBrains Mono ≈ 8.4px/char
 * → 2 chars × 8.4 = ~16.8px per level. We use 17px to stay sharp on screen.
 */
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useCallback, useState } from 'react';

// ── Shared style constants ────────────────────────────────────────────────────
// Must mirror Monaco options in Index.jsx: fontSize=14, fontFamily, tabSize=2.
const EDITOR_FONT = "'JetBrains Mono', 'Fira Code', 'Cascadia Code', Menlo, monospace";
const EDITOR_FONT_SIZE = 14; // px — matches Monaco fontSize
const EDITOR_LINE_HEIGHT = 21; // px — matches Monaco's default 1.5× line height
const INDENT_PX = 17; // ≈ 2 chars × 8.4px at 14px mono font

// ── Value colour palette — mirrors Monaco JSON token colours ─────────────────
const STRING_COLOR = '#ce9178'; // Monaco vs-dark string literal
const NUMBER_COLOR = '#b5cea8'; // Monaco vs-dark number literal
const BOOL_COLOR = '#569cd6'; // Monaco vs-dark keyword (true/false)
const NULL_COLOR = '#569cd6'; // Monaco vs-dark keyword (null)
const KEY_COLOR = '#9cdcfe'; // Monaco vs-dark property key
const BRACKET_COLOR = '#ffd700'; // Monaco vs-dark bracket colour (level 0)
const DIM_COLOR = 'rgba(255,255,255,0.35)';

function renderValue(val) {
  if (val === null) return <span style={{ color: NULL_COLOR }}>null</span>;
  if (typeof val === 'string') return <span style={{ color: STRING_COLOR }}>"{val}"</span>;
  if (typeof val === 'boolean') return <span style={{ color: BOOL_COLOR }}>{String(val)}</span>;
  if (typeof val === 'number') return <span style={{ color: NUMBER_COLOR }}>{val}</span>;
  return null;
}

function TreeNode({ nodeKey, value, depth = 0 }) {
  const [isOpen, setIsOpen] = useState(depth < 2);

  const isArray = Array.isArray(value);
  const isExpandable = value !== null && (isArray || typeof value === 'object');

  const indent = depth * INDENT_PX;
  const childEntries = isExpandable ? Object.entries(value ?? {}) : [];
  const bracketOpen = isArray ? '[' : '{';
  const bracketClose = isArray ? ']' : '}';

  const baseStyle = {
    fontFamily: EDITOR_FONT,
    fontSize: EDITOR_FONT_SIZE,
    lineHeight: `${EDITOR_LINE_HEIGHT}px`,
    paddingLeft: indent,
    whiteSpace: 'pre',
  };

  const keyNode = nodeKey !== null && (
    <span style={{ color: KEY_COLOR }}>
      {typeof nodeKey === 'number' ? nodeKey : `"${nodeKey}"`}
      <span style={{ color: 'rgba(255,255,255,0.6)' }}>: </span>
    </span>
  );

  if (!isExpandable) {
    return (
      <div style={baseStyle} className="flex items-baseline">
        {keyNode}
        {renderValue(value)}
      </div>
    );
  }

  return (
    <div>
      <button
        onClick={() => setIsOpen((o) => !o)}
        style={baseStyle}
        className="flex w-full items-center text-left hover:bg-white/[0.04] transition-colors"
      >
        <span
          className="mr-[2px] inline-flex shrink-0 items-center"
          style={{ color: DIM_COLOR, width: 14 }}
        >
          {isOpen ? (
            <ChevronDown style={{ width: 11, height: 11 }} />
          ) : (
            <ChevronRight style={{ width: 11, height: 11 }} />
          )}
        </span>

        {keyNode}

        <span style={{ color: BRACKET_COLOR }}>{bracketOpen}</span>

        {!isOpen && (
          <>
            <span style={{ color: DIM_COLOR, marginLeft: 6 }}>
              {childEntries.length} {childEntries.length === 1 ? 'item' : 'items'}
            </span>
            <span style={{ color: BRACKET_COLOR }}>{bracketClose}</span>
          </>
        )}
      </button>

      {isOpen && (
        <div>
          {childEntries.map(([k, v], idx) => (
            <TreeNode
              key={`${depth}-${k}-${idx}`}
              nodeKey={isArray ? idx : k}
              value={v}
              depth={depth + 1}
            />
          ))}
          {/* Closing bracket re-indented to same level as the opening line */}
          <div
            style={{
              ...baseStyle,
              paddingLeft: indent,
              color: BRACKET_COLOR,
            }}
          >
            {bracketClose}
          </div>
        </div>
      )}
    </div>
  );
}

export default function NucleusTreeView({ json }) {
  const parsed = useCallback(() => {
    try {
      return JSON.parse(json);
    } catch (_error) {
      return null;
    }
  }, [json])();

  if (!parsed) {
    return (
      <div
        className="flex h-full items-center justify-center bg-[#1e1e1e]"
        style={{ fontFamily: EDITOR_FONT, fontSize: 13, color: DIM_COLOR }}
      >
        Invalid JSON — fix errors in the editor to see the tree view.
      </div>
    );
  }

  return (
    <div
      className="h-full overflow-auto bg-[#1e1e1e]"
      style={{ padding: '12px 0' }} // matches Monaco padding: { top: 12, bottom: 12 }
    >
      <div style={{ paddingLeft: 12, paddingRight: 12 }}>
        <TreeNode nodeKey={null} value={parsed} depth={0} />
      </div>
    </div>
  );
}
