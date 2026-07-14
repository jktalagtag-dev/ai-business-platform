import { useTicketStatistics } from '@/modules/ticket/hooks/useTicketStatistics';
import type { TicketPriority } from '@/modules/ticket/types';

const PRIORITIES: TicketPriority[] = ['low', 'medium', 'high', 'critical'];

const LABEL: Record<TicketPriority, string> = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  critical: 'Critical',
};

/** Same semantic colors as `TicketPriorityBadge`'s variant mapping, applied
 * as bar fills instead of badge backgrounds. */
const BAR_COLOR: Record<TicketPriority, string> = {
  low: 'bg-secondary-foreground/40',
  medium: 'bg-primary',
  high: 'bg-amber-500',
  critical: 'bg-destructive',
};

/** Hand-rolled horizontal bar chart — no charting library in this project,
 * and four fixed categories don't warrant adding one. Reads the same
 * `by_priority` breakdown from `GET /tickets/statistics` that `TicketStatsBar`
 * already uses, just rendered in full rather than collapsed to "Critical". */
export function TicketPriorityChart() {
  const { data, isLoading } = useTicketStatistics();

  if (isLoading || !data) return null;

  const counts = PRIORITIES.map((p) => data.by_priority[p] ?? 0);
  const max = Math.max(1, ...counts);

  return (
    <div className="space-y-3">
      {PRIORITIES.map((priority, i) => {
        const count = counts[i];
        return (
          <div key={priority} className="flex items-center gap-3">
            <span className="w-16 shrink-0 text-sm text-muted-foreground">{LABEL[priority]}</span>
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
              <div
                className={`h-full rounded-full ${BAR_COLOR[priority]}`}
                style={{ width: `${(count / max) * 100}%` }}
              />
            </div>
            <span className="w-8 shrink-0 text-right text-sm font-medium">{count}</span>
          </div>
        );
      })}
    </div>
  );
}
