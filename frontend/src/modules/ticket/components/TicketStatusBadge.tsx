import { Badge, type BadgeProps } from '@/components/ui/badge';
import type { TicketStatus } from '@/modules/ticket/types';

const VARIANT: Record<TicketStatus, NonNullable<BadgeProps['variant']>> = {
  open: 'secondary',
  assigned: 'default',
  in_progress: 'default',
  waiting_for_user: 'warning',
  resolved: 'success',
  cancelled: 'secondary',
  closed: 'success',
};

const LABEL: Record<TicketStatus, string> = {
  open: 'Open',
  assigned: 'Assigned',
  in_progress: 'In progress',
  waiting_for_user: 'Waiting for user',
  resolved: 'Resolved',
  cancelled: 'Cancelled',
  closed: 'Closed',
};

export function TicketStatusBadge({ status }: { status: TicketStatus }) {
  return <Badge variant={VARIANT[status]}>{LABEL[status]}</Badge>;
}
