import { Card, CardContent } from '@/components/ui/card';

/** Same four categories and bar-fill colors as the real TicketPriorityChart,
 * with fabricated counts — a static mock, not a second chart implementation. */
const BARS: { label: string; count: number; color: string }[] = [
  { label: 'Low', count: 5, color: 'bg-secondary-foreground/40' },
  { label: 'Medium', count: 9, color: 'bg-primary' },
  { label: 'High', count: 6, color: 'bg-warning' },
  { label: 'Critical', count: 3, color: 'bg-destructive' },
];

const STATS: { label: string; value: string }[] = [
  { label: 'Avg. resolution time', value: '4.2h' },
  { label: 'Tickets closed this month', value: '142' },
  { label: 'Low-stock alerts caught', value: '27' },
];

export function AnalyticsSection() {
  const max = Math.max(...BARS.map((b) => b.count));

  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <div>
          <h2 className="text-h2 font-bold tracking-tight">See what's actually happening</h2>
          <p className="mt-4 text-muted-foreground">
            A dashboard built from your real ticket, inventory, and workflow data — not vanity
            metrics — so you know what needs attention before it becomes a problem.
          </p>
          <dl className="mt-8 grid grid-cols-3 gap-4">
            {STATS.map((stat) => (
              <div key={stat.label}>
                <dt className="text-xs text-muted-foreground">{stat.label}</dt>
                <dd className="mt-1 text-lg font-semibold tracking-tight">{stat.value}</dd>
              </div>
            ))}
          </dl>
        </div>

        <Card>
          <CardContent className="space-y-3 p-6">
            <p className="text-sm font-medium text-muted-foreground">Tickets by priority</p>
            {BARS.map((bar) => (
              <div key={bar.label} className="flex items-center gap-3">
                <span className="w-16 shrink-0 text-sm text-muted-foreground">{bar.label}</span>
                <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                  <div
                    className={`h-full rounded-full ${bar.color}`}
                    style={{ width: `${(bar.count / max) * 100}%` }}
                  />
                </div>
                <span className="w-8 shrink-0 text-right text-sm font-medium">{bar.count}</span>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </section>
  );
}
