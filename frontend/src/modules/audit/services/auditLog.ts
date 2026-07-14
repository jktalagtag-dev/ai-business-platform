import { api } from '@/lib/api-client';
import type { AuditLogListParams, AuditLogResource } from '@/modules/audit/types';

export const auditLogService = {
  list: (params: AuditLogListParams = {}) =>
    api.getPage<AuditLogResource>('/audit-logs', { query: { ...params } }),
};
