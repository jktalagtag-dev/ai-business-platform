import { useTicketStatistics } from '@/modules/ticket/hooks/useTicketStatistics';

function Tile({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-lg border p-4">
      <div className="text-2xl font-semibold tracking-tight">{value}</div>
      <div className="text-sm text-muted-foreground">{label}</div>
    </div>
  );
}

/** Scoped identically to the ticket list — a plain member sees their own
 * totals, tickets.view holders see the tenant-wide totals. */
export function TicketStatsBar() {
  const { data, isLoading } = useTicketStatistics();

  if (isLoading || !data) return null;

  const criticalCount = data.by_priority.critical ?? 0;
  const avgResolution =
    data.average_resolution_minutes === null
      ? '—'
      : data.average_resolution_minutes >= 60
        ? `${(data.average_resolution_minutes / 60).toFixed(1)}h`
        : `${Math.round(data.average_resolution_minutes)}m`;

  return (
    <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
      <Tile label="Open" value={String(data.open_count)} />
      <Tile label="Closed" value={String(data.closed_count)} />
      <Tile label="Critical" value={String(criticalCount)} />
      <Tile label="Avg. resolution" value={avgResolution} />
    </div>
  );
}
