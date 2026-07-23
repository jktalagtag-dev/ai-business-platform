import { Link } from 'react-router-dom';
import { Check } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/cn';
import { paths } from '@/routes/routes.config';

/** The three `tenants.plan` values this app actually stores (see the
 * `tenants` migration's `plan` column, default `'free'`) — this table is
 * presentational copy over that same field, not a separate billing system. */
const TIERS: {
  plan: 'free' | 'pro' | 'enterprise';
  name: string;
  price: string;
  cadence: string;
  description: string;
  features: string[];
  highlighted?: boolean;
}[] = [
  {
    plan: 'free',
    name: 'Free',
    price: '$0',
    cadence: 'forever',
    description: 'For small teams getting started.',
    features: [
      'Up to 5 team members',
      'Inventory, Tickets, and HR modules',
      'CSV export on every table',
      'Community support',
    ],
  },
  {
    plan: 'pro',
    name: 'Pro',
    price: '$49',
    cadence: 'per month',
    description: 'For growing teams that need automation.',
    features: [
      'Unlimited team members',
      'AI Assistant + Knowledge Base',
      'Workflow automation',
      'Priority support',
    ],
    highlighted: true,
  },
  {
    plan: 'enterprise',
    name: 'Enterprise',
    price: 'Custom',
    cadence: 'billed annually',
    description: 'For larger organizations with custom needs.',
    features: [
      'Everything in Pro',
      'Custom roles & permissions',
      'Audit log & compliance exports',
      'Dedicated support',
    ],
  },
];

export function PricingSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-2xl text-center">
        <h2 className="text-h2 font-bold tracking-tight">Simple, transparent pricing</h2>
        <p className="mt-4 text-muted-foreground">Start free. Upgrade when your team needs more.</p>
      </div>

      <div className="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {TIERS.map((tier) => (
          <Card
            key={tier.plan}
            className={cn(tier.highlighted && 'border-primary shadow-elevation-2')}
          >
            <CardContent className="flex h-full flex-col p-6">
              <div className="flex items-center gap-2">
                <h3 className="text-title font-semibold">{tier.name}</h3>
                {tier.highlighted && <Badge>Most popular</Badge>}
              </div>
              <p className="mt-1 text-sm text-muted-foreground">{tier.description}</p>
              <div className="mt-4">
                <span className="text-h2 font-bold tracking-tight">{tier.price}</span>
                <span className="ml-1 text-sm text-muted-foreground">{tier.cadence}</span>
              </div>
              <ul className="mt-6 space-y-3 text-sm">
                {tier.features.map((feature) => (
                  <li key={feature} className="flex items-start gap-2">
                    <Check className="mt-0.5 h-4 w-4 shrink-0 text-success" strokeWidth={1.75} />
                    <span>{feature}</span>
                  </li>
                ))}
              </ul>
              <Button
                className="mt-8"
                variant={tier.highlighted ? 'default' : 'outline'}
                asChild
              >
                <Link to={paths.register}>Get started</Link>
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </section>
  );
}
