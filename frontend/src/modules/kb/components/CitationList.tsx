import type { Citation } from '@/modules/kb/types';

export function CitationList({ citations }: { citations: Citation[] }) {
  if (citations.length === 0) return null;

  return (
    <ol className="mt-2 space-y-1.5 text-xs text-muted-foreground">
      {citations.map((citation) => (
        <li key={citation.number} className="rounded-md border border-dashed bg-muted/40 px-2 py-1.5">
          <span className="font-medium text-foreground">
            [{citation.number}] {citation.title}
          </span>{' '}
          · page {citation.page_number} · relevance {(citation.score * 100).toFixed(0)}%
          <p className="mt-0.5 italic">&ldquo;{citation.snippet}&rdquo;</p>
        </li>
      ))}
    </ol>
  );
}
