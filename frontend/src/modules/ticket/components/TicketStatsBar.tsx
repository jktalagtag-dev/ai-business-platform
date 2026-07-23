import type { LucideIcon } from 'lucide-react';
import { CircleDot, CheckCircle2, AlertTriangle, Timer } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/cn';
import { useTicketStatistics } from '@/modules/ticket/hooks/useTicketStatistics';

function Tile({
  label,
  value,
  icon: Icon,
  accent,
}: {
  label: string;
  value: string;
  icon: LucideIcon;
  accent: string;
}) {
  return (
    <Card>
      <CardContent className="flex items-center gap-4 p-4">
        <div className={cn('flex h-10 w-10 shrink-0 items-center justify-center rounded-lg', accent)}>
          <Icon className="h-5 w-5" strokeWidth={1.75} />
        </div>
        <div className="min-w-0">
          <div className="text-2xl font-semibold tracking-tight">{value}</div>
          <div className="truncate text-sm text-muted-foreground">{label}</div>
        </div>
      </CardContent>
    </Card>
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
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
      <Tile
        label="Open"
        value={String(data.open_count)}
        icon={CircleDot}
        accent="bg-info/15 text-info"
      />
      <Tile
        label="Closed"
        value={String(data.closed_count)}
        icon={CheckCircle2}
        accent="bg-success/15 text-success"
      />
      <Tile
        label="Critical"
        value={String(criticalCount)}
        icon={AlertTriangle}
        accent="bg-destructive/15 text-destructive"
      />
      <Tile
        label="Avg. resolution"
        value={avgResolution}
        icon={Timer}
        accent="bg-primary/15 text-primary"
      />
    </div>
  );
}
