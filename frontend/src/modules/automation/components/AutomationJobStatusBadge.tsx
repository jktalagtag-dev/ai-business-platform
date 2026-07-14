import { Badge, type BadgeProps } from '@/components/ui/badge';
import type { AutomationJobStatus } from '@/modules/automation/types';

const VARIANT: Record<AutomationJobStatus, NonNullable<BadgeProps['variant']>> = {
  queued: 'secondary',
  running: 'warning',
  succeeded: 'success',
  failed: 'destructive',
};

const LABEL: Record<AutomationJobStatus, string> = {
  queued: 'Queued',
  running: 'Running',
  succeeded: 'Succeeded',
  failed: 'Failed',
};

export function AutomationJobStatusBadge({ status }: { status: AutomationJobStatus }) {
  return <Badge variant={VARIANT[status]}>{LABEL[status]}</Badge>;
}
