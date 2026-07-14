import { Badge, type BadgeProps } from '@/components/ui/badge';
import type { WorkflowStatus } from '@/modules/automation/types';

const VARIANT: Record<WorkflowStatus, NonNullable<BadgeProps['variant']>> = {
  draft: 'secondary',
  active: 'success',
  paused: 'warning',
};

const LABEL: Record<WorkflowStatus, string> = {
  draft: 'Draft',
  active: 'Active',
  paused: 'Paused',
};

export function WorkflowStatusBadge({ status }: { status: WorkflowStatus }) {
  return <Badge variant={VARIANT[status]}>{LABEL[status]}</Badge>;
}
