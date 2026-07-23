import { AlertTriangle, CheckCircle2, CircleDot, Timer, type LucideIcon } from 'lucide-react';
import { Badge, type BadgeProps } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/cn';

const STAT_TILES: { label: string; value: string; icon: LucideIcon; accent: string }[] = [
  { label: 'Open', value: '18', icon: CircleDot, accent: 'bg-info/15 text-info' },
  { label: 'Closed', value: '142', icon: CheckCircle2, accent: 'bg-success/15 text-success' },
  { label: 'Critical', value: '3', icon: AlertTriangle, accent: 'bg-destructive/15 text-destructive' },
  { label: 'Avg. resolution', value: '4.2h', icon: Timer, accent: 'bg-primary/15 text-primary' },
];

const SAMPLE_ROWS: { name: string; department: string; status: BadgeProps['variant']; statusLabel: string }[] = [
  { name: 'Printer offline in Finance', department: 'Finance', status: 'destructive', statusLabel: 'Critical' },
  { name: 'Reset VPN credentials', department: 'IT', status: 'warning', statusLabel: 'In progress' },
  { name: 'Onboard new hire laptop', department: 'HR', status: 'success', statusLabel: 'Resolved' },
];

/**
 * A stylized, static mock of the real dashboard — built from this app's own
 * Card/Badge components so it reads as an actual product screenshot rather
 * than illustration work (DESIGN_SYSTEM.md's product-first rule), but with
 * fabricated example numbers: this renders for signed-out visitors who have
 * no tenant data to show.
 */
export function ProductPreviewMock() {
  return (
    <div className="mx-auto max-w-4xl overflow-hidden rounded-dialog border bg-card shadow-elevation-3">
      <div className="flex items-center gap-1.5 border-b bg-muted/40 px-4 py-3">
        <span className="h-3 w-3 rounded-full bg-destructive/40" />
        <span className="h-3 w-3 rounded-full bg-warning/40" />
        <span className="h-3 w-3 rounded-full bg-success/40" />
      </div>

      <div className="space-y-6 p-6 sm:p-8">
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {STAT_TILES.map((tile) => (
            <Card key={tile.label}>
              <CardContent className="flex items-center gap-3 p-4">
                <div
                  className={cn(
                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                    tile.accent
                  )}
                >
                  <tile.icon className="h-4 w-4" strokeWidth={1.75} />
                </div>
                <div className="min-w-0">
                  <div className="text-lg font-semibold tracking-tight">{tile.value}</div>
                  <div className="truncate text-xs text-muted-foreground">{tile.label}</div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        <Card>
          <CardContent className="p-0">
            <table className="w-full text-sm">
              <tbody>
                {SAMPLE_ROWS.map((row, i) => (
                  <tr key={row.name} className={cn('border-b last:border-0', i % 2 === 1 && 'bg-muted/40')}>
                    <td className="px-4 py-3 font-medium">{row.name}</td>
                    <td className="px-4 py-3 text-muted-foreground">{row.department}</td>
                    <td className="px-4 py-3 text-right">
                      <Badge variant={row.status}>{row.statusLabel}</Badge>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
