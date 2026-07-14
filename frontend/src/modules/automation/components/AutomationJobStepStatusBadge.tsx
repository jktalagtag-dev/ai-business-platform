import { Badge, type BadgeProps } from '@/components/ui/badge';
import type { AutomationJobStepStatus } from '@/modules/automation/types';

const VARIANT: Record<AutomationJobStepStatus, NonNullable<BadgeProps['variant']>> = {
  pending: 'secondary',
  running: 'warning',
  succeeded: 'success',
  failed: 'destructive',
  skipped: 'outline',
};

const LABEL: Record<AutomationJobStepStatus, string> = {
  pending: 'Pending',
  running: 'Running',
  succeeded: 'Succeeded',
  failed: 'Failed',
  skipped: 'Skipped',
};

export function AutomationJobStepStatusBadge({ status }: { status: AutomationJobStepStatus }) {
  return <Badge variant={VARIANT[status]}>{LABEL[status]}</Badge>;
}
