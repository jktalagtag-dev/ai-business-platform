import { Badge, type BadgeProps } from '@/components/ui/badge';
import type { TicketPriority } from '@/modules/ticket/types';

const VARIANT: Record<TicketPriority, NonNullable<BadgeProps['variant']>> = {
  low: 'secondary',
  medium: 'default',
  high: 'warning',
  critical: 'destructive',
};

const LABEL: Record<TicketPriority, string> = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  critical: 'Critical',
};

export function TicketPriorityBadge({ priority }: { priority: TicketPriority }) {
  return <Badge variant={VARIANT[priority]}>{LABEL[priority]}</Badge>;
}
