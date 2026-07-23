import { ArrowRight, Bell, Ticket, UserCheck, type LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

const PIPELINE: { icon: LucideIcon; label: string; detail: string }[] = [
  { icon: Ticket, label: 'Trigger', detail: 'Ticket created' },
  { icon: UserCheck, label: 'Step', detail: 'Assign to IT department' },
  { icon: Bell, label: 'Action', detail: 'Notify the department manager' },
];

/**
 * A static mock of a workflow pipeline — the real builder (see
 * WorkflowDetailPage) lets a trigger fan out into ordered steps and actions;
 * this is a simplified, three-box version of that same shape for the
 * landing page, not a second implementation of the builder.
 */
export function AutomationSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <div>
          <h2 className="text-h2 font-bold tracking-tight">Automate the busywork</h2>
          <p className="mt-4 text-muted-foreground">
            Build multi-step workflows that trigger on real events — a new ticket, a low-stock
            product, a new hire — and chain together assignments, notifications, and updates
            without writing a line of code.
          </p>
        </div>

        <Card>
          <CardContent className="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:gap-3">
            {PIPELINE.map((step, i) => (
              <div key={step.label} className="flex flex-1 items-center gap-3 sm:flex-col sm:text-center">
                <div className="flex flex-1 items-center gap-3 rounded-lg border bg-background p-4 sm:flex-col sm:text-center">
                  <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/15 text-primary">
                    <step.icon className="h-4 w-4" strokeWidth={1.75} />
                  </div>
                  <div className="min-w-0">
                    <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                      {step.label}
                    </div>
                    <div className="text-sm font-medium">{step.detail}</div>
                  </div>
                </div>
                {i < PIPELINE.length - 1 && (
                  <ArrowRight className="h-4 w-4 shrink-0 rotate-90 text-muted-foreground sm:rotate-0" />
                )}
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </section>
  );
}
