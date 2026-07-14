import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Loader2, Pencil, UserPlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { PageHeader } from '@/components/layout/PageHeader';
import { ErrorState } from '@/components/layout/ErrorState';
import { useDepartment } from '@/modules/employee/hooks/useDepartments';
import { useEmployee } from '@/modules/employee/hooks/useEmployees';
import { useTicket } from '@/modules/ticket/hooks/useTickets';
import { useTicketAbilities } from '@/modules/ticket/hooks/useTicketAbilities';
import { TicketFormDialog } from '@/modules/ticket/components/TicketFormDialog';
import { AssignTicketDialog } from '@/modules/ticket/components/AssignTicketDialog';
import { UpdateStatusDialog } from '@/modules/ticket/components/UpdateStatusDialog';
import { CloseTicketDialog } from '@/modules/ticket/components/CloseTicketDialog';
import { ReopenTicketDialog } from '@/modules/ticket/components/ReopenTicketDialog';
import { TicketPriorityBadge } from '@/modules/ticket/components/TicketPriorityBadge';
import { TicketStatusBadge } from '@/modules/ticket/components/TicketStatusBadge';
import { TicketCommentsPanel } from '@/modules/ticket/components/TicketCommentsPanel';
import { TicketAttachmentsPanel } from '@/modules/ticket/components/TicketAttachmentsPanel';

const TYPE_LABEL: Record<string, string> = {
  hardware: 'Hardware',
  software: 'Software',
  network: 'Network',
  account_access: 'Account access',
  printer: 'Printer',
  email: 'Email',
  security: 'Security',
  other: 'Other',
};

export function TicketDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { data: ticket, isLoading, isError, refetch } = useTicket(id);

  const [editOpen, setEditOpen] = useState(false);
  const [assignOpen, setAssignOpen] = useState(false);
  const [statusOpen, setStatusOpen] = useState(false);
  const [closeOpen, setCloseOpen] = useState(false);
  const [reopenOpen, setReopenOpen] = useState(false);

  const { data: requester } = useEmployee(ticket?.attributes.employee_id);
  const { data: technician } = useEmployee(ticket?.attributes.assigned_technician_id ?? undefined);
  const { data: department } = useDepartment(ticket?.attributes.department_id ?? undefined);

  const abilities = useTicketAbilities(
    ticket ?? {
      id: '',
      type: 'ticket',
      attributes: {
        ticket_number: '',
        employee_id: '',
        assigned_technician_id: null,
        department_id: null,
        ticket_type: 'other',
        priority: 'low',
        status: 'open',
        subject: '',
        description: '',
        resolution_notes: null,
        resolved_at: null,
        closed_at: null,
        sla_breached_at: null,
        created_at: '',
      },
    }
  );

  if (isLoading) {
    return (
      <div className="flex items-center justify-center rounded-lg border p-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (isError || !ticket) {
    return <ErrorState message="Ticket not found." onRetry={() => refetch()} />;
  }

  const { canManage, canEdit, canClose, canReopen } = abilities;
  const { status } = ticket.attributes;
  const isTerminal = status === 'closed' || status === 'cancelled';
  const isReopenable = status === 'resolved' || status === 'closed';

  return (
    <div className="space-y-6">
      <PageHeader
        title={ticket.attributes.subject}
        description={ticket.attributes.ticket_number}
        actions={
          <div className="flex flex-wrap gap-2">
            {canEdit && (
              <Button variant="outline" onClick={() => setEditOpen(true)}>
                <Pencil className="h-4 w-4" />
                Edit
              </Button>
            )}
            {canManage && (
              <Button variant="outline" onClick={() => setAssignOpen(true)}>
                <UserPlus className="h-4 w-4" />
                Assign
              </Button>
            )}
            {canEdit && !isTerminal && (
              <Button variant="outline" onClick={() => setStatusOpen(true)}>
                Update status
              </Button>
            )}
            {canClose && !isTerminal && (
              <Button variant="outline" onClick={() => setCloseOpen(true)}>
                Close
              </Button>
            )}
            {canReopen && isReopenable && (
              <Button variant="outline" onClick={() => setReopenOpen(true)}>
                Reopen
              </Button>
            )}
          </div>
        }
      />

      <Card>
        <CardContent className="space-y-4 pt-6">
          <div className="flex flex-wrap items-center gap-2">
            <TicketStatusBadge status={ticket.attributes.status} />
            <TicketPriorityBadge priority={ticket.attributes.priority} />
            <span className="text-sm text-muted-foreground">
              {TYPE_LABEL[ticket.attributes.ticket_type]}
            </span>
          </div>

          <p className="whitespace-pre-wrap text-sm">{ticket.attributes.description}</p>

          <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
            <div>
              <div className="text-muted-foreground">Requester</div>
              <div>{requester?.attributes.full_name ?? '—'}</div>
            </div>
            <div>
              <div className="text-muted-foreground">Technician</div>
              <div>{technician?.attributes.full_name ?? '—'}</div>
            </div>
            <div>
              <div className="text-muted-foreground">Department</div>
              <div>{department?.attributes.name ?? '—'}</div>
            </div>
          </div>

          {ticket.attributes.resolution_notes && (
            <div>
              <div className="text-sm text-muted-foreground">Resolution notes</div>
              <p className="whitespace-pre-wrap text-sm">{ticket.attributes.resolution_notes}</p>
            </div>
          )}

          {ticket.attributes.sla_breached_at && (
            <p className="text-sm text-destructive">
              SLA breached at {new Date(ticket.attributes.sla_breached_at).toLocaleString()}
            </p>
          )}
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <div>
          <h2 className="mb-3 text-lg font-semibold">Comments</h2>
          <TicketCommentsPanel ticket={ticket} />
        </div>
        <div>
          <h2 className="mb-3 text-lg font-semibold">Attachments</h2>
          <TicketAttachmentsPanel ticket={ticket} />
        </div>
      </div>

      <TicketFormDialog open={editOpen} onOpenChange={setEditOpen} ticket={ticket} />
      <AssignTicketDialog open={assignOpen} onOpenChange={setAssignOpen} ticket={ticket} />
      <UpdateStatusDialog open={statusOpen} onOpenChange={setStatusOpen} ticket={ticket} />
      <CloseTicketDialog open={closeOpen} onOpenChange={setCloseOpen} ticketId={ticket.id} />
      <ReopenTicketDialog open={reopenOpen} onOpenChange={setReopenOpen} ticketId={ticket.id} />
    </div>
  );
}
