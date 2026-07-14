import { Search, Wrench } from 'lucide-react';

function formatToolName(name: string): string {
  return name.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

/** Best-effort readable label for one result item — the exact shape of a
 * tool's `result` varies per tool (e.g. Knowledge Base citations vs ticket
 * stats), so this looks for common "title-ish" keys and falls back to a
 * truncated JSON dump rather than assuming a fixed schema. */
function describeResultItem(item: unknown): string {
  if (item && typeof item === 'object') {
    const obj = item as Record<string, unknown>;
    const label = obj.title ?? obj.document_title ?? obj.name ?? obj.snippet ?? obj.excerpt;
    if (typeof label === 'string') return label;
  }
  const json = JSON.stringify(item);
  return json.length > 140 ? `${json.slice(0, 140)}…` : json;
}

export function ToolCallBlock({ kind, name, args, content }: {
  kind: 'call' | 'result';
  name: string;
  args?: Record<string, unknown>;
  content?: string;
}) {
  const parsed = (() => {
    if (!content) return null;
    try {
      return JSON.parse(content) as unknown;
    } catch {
      return null;
    }
  })();

  const resultItems =
    parsed && typeof parsed === 'object' && Array.isArray((parsed as Record<string, unknown>).results)
      ? ((parsed as Record<string, unknown>).results as unknown[])
      : null;

  return (
    <div className="max-w-lg rounded-lg border border-dashed bg-muted/40 px-3 py-2 text-sm">
      <div className="flex items-center gap-2 text-muted-foreground">
        {kind === 'call' ? (
          <Wrench className="h-3.5 w-3.5 shrink-0" />
        ) : (
          <Search className="h-3.5 w-3.5 shrink-0" />
        )}
        <span>
          {kind === 'call' ? `Calling ${formatToolName(name)}…` : `${formatToolName(name)} result`}
        </span>
      </div>

      {resultItems && resultItems.length > 0 && (
        <ul className="mt-2 list-disc space-y-1 pl-5 text-xs">
          {resultItems.slice(0, 5).map((item, i) => (
            <li key={i}>{describeResultItem(item)}</li>
          ))}
        </ul>
      )}

      {Boolean(args || (parsed && !resultItems)) && (
        <details className="mt-2">
          <summary className="cursor-pointer text-xs text-muted-foreground">View details</summary>
          <pre className="mt-1 overflow-x-auto rounded bg-background p-2 text-xs">
            {JSON.stringify(args ?? parsed, null, 2)}
          </pre>
        </details>
      )}
    </div>
  );
}
