/**
 * NucleusStatusBar
 *
 * Bottom status bar displaying JSON validity, character count,
 * line count, and current cursor mode.
 */
export default function NucleusStatusBar({ json, isValidJson, isEmptyJson, errorMessage, cursor }) {
  const charCount = json?.length ?? 0;
  const lineCount = (json?.match(/\n/g) ?? []).length + 1;

  const ln = cursor?.lineNumber ?? lineCount;
  const col = cursor?.column ?? null;

  const statusColor = isEmptyJson
    ? 'text-muted-foreground'
    : isValidJson
      ? 'text-emerald-400'
      : 'text-destructive';

  const dotColor = isEmptyJson
    ? 'bg-muted-foreground'
    : isValidJson
      ? 'bg-emerald-400'
      : 'bg-destructive';

  const statusLabel = isEmptyJson
    ? 'Empty'
    : isValidJson
      ? 'JSON Valid'
      : errorMessage || 'JSON Invalid';

  return (
    <div className="flex items-center justify-between border-t border-border bg-card px-4 py-1.5 text-[11px] text-muted-foreground">
      <div className="flex items-center gap-4">
        <span className={`flex items-center gap-1.5 font-medium ${statusColor}`}>
          <span className={`inline-block h-1.5 w-1.5 rounded-full ${dotColor}`} />
          {statusLabel}
        </span>

        <span className="hidden sm:inline">
          Ln {ln}, Col {col ?? '—'}
        </span>
      </div>

      <div className="flex items-center gap-4">
        <span>{charCount.toLocaleString()} chars</span>
        <span>{lineCount.toLocaleString()} lines</span>
        <span className="hidden md:inline text-muted-foreground/60">JSON · UTF-8</span>
      </div>
    </div>
  );
}
