import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useTicketAbilities } from '@/modules/ticket/hooks/useTicketAbilities';
import { useAuthStore } from '@/store/authStore';
import { employeeService } from '@/modules/employee/services/employees';
import { makeAuthResource, makeEmployeeResource, makeTicketResource } from '@/tests/fixtures';

vi.mock('@/modules/employee/services/employees', () => ({
  employeeService: { me: vi.fn() },
}));

function wrapper({ children }: { children: ReactNode }) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}

beforeEach(() => {
  vi.clearAllMocks();
  useAuthStore.getState().clear();
});

describe('useTicketAbilities', () => {
  it('grants comment + internal-note ability to the assigned technician', async () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: [] }));
    vi.mocked(employeeService.me).mockResolvedValue(makeEmployeeResource({ id: 'employee_2' }));
    const ticket = makeTicketResource({ assigned_technician_id: 'employee_2', employee_id: 'employee_1' });

    const { result } = renderHook(() => useTicketAbilities(ticket), { wrapper });

    await waitFor(() => expect(result.current.isAssignedTechnician).toBe(true));
    expect(result.current.canComment).toBe(true);
    expect(result.current.canAddInternalNote).toBe(true);
    expect(result.current.canManage).toBe(false);
  });

  it('grants comment ability (but not internal-note) to the requester', async () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: [] }));
    vi.mocked(employeeService.me).mockResolvedValue(makeEmployeeResource({ id: 'employee_1' }));
    const ticket = makeTicketResource({ assigned_technician_id: 'employee_2', employee_id: 'employee_1' });

    const { result } = renderHook(() => useTicketAbilities(ticket), { wrapper });

    await waitFor(() => expect(result.current.isRequester).toBe(true));
    expect(result.current.canComment).toBe(true);
    expect(result.current.canAddInternalNote).toBe(false);
  });

  it('denies comment ability to an unrelated bystander with no tickets.manage', async () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: [] }));
    vi.mocked(employeeService.me).mockResolvedValue(makeEmployeeResource({ id: 'employee_3' }));
    const ticket = makeTicketResource({ assigned_technician_id: 'employee_2', employee_id: 'employee_1' });

    const { result } = renderHook(() => useTicketAbilities(ticket), { wrapper });

    await waitFor(() => expect(result.current.isRequester).toBe(false));
    expect(result.current.canComment).toBe(false);
    expect(result.current.canAddInternalNote).toBe(false);
  });

  it('grants full ability to a tickets.manage holder regardless of relationship', async () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: ['tickets.manage'] }));
    vi.mocked(employeeService.me).mockResolvedValue(makeEmployeeResource({ id: 'employee_9' }));
    const ticket = makeTicketResource({ assigned_technician_id: 'employee_2', employee_id: 'employee_1' });

    const { result } = renderHook(() => useTicketAbilities(ticket), { wrapper });

    await waitFor(() => expect(result.current.canManage).toBe(true));
    expect(result.current.canComment).toBe(true);
    expect(result.current.canAddInternalNote).toBe(true);
  });
});
