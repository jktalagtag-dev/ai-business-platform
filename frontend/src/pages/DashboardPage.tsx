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

      <div className="grid grid-cols-12 gap-6">
        <Card className="col-span-12 sm:col-span-6 lg:col-span-4">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Organization</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{tenant?.name}</p>
            <p className="text-sm text-muted-foreground">{tenant?.slug}</p>
          </CardContent>
        </Card>

        <Card className="col-span-12 sm:col-span-6 lg:col-span-4">
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

        <Card className="col-span-12 sm:col-span-6 lg:col-span-4">
          <CardHeader className="pb-2">
            <CardTitle className="text-sm text-muted-foreground">Signed in as</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{user?.attributes.name}</p>
            <p className="text-sm text-muted-foreground">{user?.attributes.email}</p>
          </CardContent>
        </Card>
      </div>

      <div className="mt-6 grid grid-cols-12 gap-6">
        <div className="col-span-12">
          <TicketStatsBar />
        </div>

        <Card className="col-span-12">
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
