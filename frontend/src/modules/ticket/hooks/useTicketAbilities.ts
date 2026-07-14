import { useAbility } from '@/hooks/useAbility';
import { useMyEmployeeProfile } from '@/modules/employee/hooks/useEmployees';
import type { TicketResource } from '@/modules/ticket/types';

/**
 * Client-side mirror of TicketPolicy's relationship-based checks
 * (addComment/addInternalNote) — a plain member's ticket access is entirely
 * relationship-based (requester/assignee), never permission-based, so this
 * compares the caller's own employee id (from GET /employees/me) against the
 * ticket's employee_id/assigned_technician_id.
 */
export function useTicketAbilities(ticket: TicketResource) {
  const canManage = useAbility('tickets.manage');
  const { data: myProfile } = useMyEmployeeProfile();

  const isRequester = myProfile?.id === ticket.attributes.employee_id;
  const isAssignedTechnician = myProfile?.id === ticket.attributes.assigned_technician_id;

  return {
    canManage,
    isRequester,
    isAssignedTechnician,
    canComment: canManage || isRequester || isAssignedTechnician,
    canAddInternalNote: canManage || isAssignedTechnician,
    // Mirrors TicketPolicy::update — assign()/updateStatus() reuse the same check.
    canEdit: canManage || isAssignedTechnician,
    canClose: canManage || isAssignedTechnician,
    canReopen: canManage || isAssignedTechnician || isRequester,
  };
}
