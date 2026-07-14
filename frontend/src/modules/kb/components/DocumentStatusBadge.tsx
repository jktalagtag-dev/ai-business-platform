import { Badge, type BadgeProps } from '@/components/ui/badge';
import type { KbDocumentStatus } from '@/modules/kb/types';

const VARIANT: Record<KbDocumentStatus, NonNullable<BadgeProps['variant']>> = {
  processing: 'warning',
  ready: 'success',
  failed: 'destructive',
};

const LABEL: Record<KbDocumentStatus, string> = {
  processing: 'Processing',
  ready: 'Ready',
  failed: 'Failed',
};

export function DocumentStatusBadge({ status }: { status: KbDocumentStatus }) {
  return <Badge variant={VARIANT[status]}>{LABEL[status]}</Badge>;
}
