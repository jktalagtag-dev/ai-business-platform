import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageHeader } from '@/components/layout/PageHeader';
import { useAuth } from '@/hooks/useAuth';
import { useTenant } from '@/hooks/useTenant';
import { TicketStatsBar } from '@/modules/ticket/components/TicketStatsBar';
import { TicketPriorityChart } from '@/modules/ticket/components/TicketPriorityChart';

export function DashboardPage() {
  const { user, role } = useAuth();
  const tenant = useTenant();

  return (
    <>
      <PageHeader
        title={`Welcome, ${user?.attributes.name ?? ''}`}
        description="Your workspace at a glance."
      />

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Organization</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{tenant?.name}</p>
            <p className="text-sm text-muted-foreground">{tenant?.slug}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Your role</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{role?.name}</p>
            <p className="text-sm text-muted-foreground">
              {role?.permissions.length ?? 0} permissions
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Signed in as</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{user?.attributes.name}</p>
            <p className="text-sm text-muted-foreground">{user?.attributes.email}</p>
          </CardContent>
        </Card>
      </div>

      <div className="mt-6">
        <TicketStatsBar />

        <Card>
          <CardHeader>
            <CardTitle>Tickets by priority</CardTitle>
          </CardHeader>
          <CardContent>
            <TicketPriorityChart />
          </CardContent>
        </Card>
      </div>
    </>
  );
}
