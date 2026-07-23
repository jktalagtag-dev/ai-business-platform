import { BookOpen, Boxes, MessageSquare, Ticket, Users, Workflow, type LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

/** One card per module that actually ships today — deliberately the same
 * six areas (plus icons) as the authenticated app's own sidebar nav, so
 * this grid never promises something the product doesn't do yet. */
const MODULES: { title: string; description: string; icon: LucideIcon }[] = [
  {
    title: 'Inventory',
    description: 'Track products, categories, suppliers, and stock levels with full movement history.',
    icon: Boxes,
  },
  {
    title: 'HR & Employees',
    description: 'A directory for your team — departments, positions, and employee records in one place.',
    icon: Users,
  },
  {
    title: 'Tickets',
    description: 'Route and resolve internal support requests, scoped to the right department automatically.',
    icon: Ticket,
  },
  {
    title: 'AI Assistant',
    description: 'A chat assistant that can actually look up your tickets, inventory, and employees — not just talk about them.',
    icon: MessageSquare,
  },
  {
    title: 'Knowledge Base',
    description: 'Upload documents once; the assistant retrieves and cites them when answering questions.',
    icon: BookOpen,
  },
  {
    title: 'Automation',
    description: 'Build multi-step workflows that react to events in your data, no code required.',
    icon: Workflow,
  },
];

export function ModulesSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-2xl text-center">
        <h2 className="text-h2 font-bold tracking-tight">Everything runs on one platform</h2>
        <p className="mt-4 text-muted-foreground">
          No more stitching together five different tools and hoping the data lines up.
        </p>
      </div>

      <div className="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {MODULES.map((module) => (
          <Card key={module.title}>
            <CardContent className="p-6">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/15 text-primary">
                <module.icon className="h-5 w-5" strokeWidth={1.75} />
              </div>
              <h3 className="mt-4 text-title font-semibold">{module.title}</h3>
              <p className="mt-2 text-sm text-muted-foreground">{module.description}</p>
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  );
}
