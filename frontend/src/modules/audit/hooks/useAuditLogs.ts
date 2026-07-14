import { useQuery } from '@tanstack/react-query';
import { auditLogService } from '@/modules/audit/services/auditLog';
import type { AuditLogListParams, AuditLogResource } from '@/modules/audit/types';
import type { Page } from '@/types/api';

export function useAuditLogs(filter: AuditLogListParams = {}) {
  return useQuery<Page<AuditLogResource>>({
    queryKey: ['audit', 'logs', filter],
    queryFn: () => auditLogService.list(filter),
  });
}
