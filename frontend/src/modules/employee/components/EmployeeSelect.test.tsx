import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { EmployeeSelect } from '@/modules/employee/components/EmployeeSelect';
import { employeeService } from '@/modules/employee/services/employees';
import { makeEmployeeResource } from '@/tests/fixtures';

vi.mock('@/modules/employee/services/employees', () => ({
  employeeService: { list: vi.fn() },
}));

function renderSelect(props: Partial<React.ComponentProps<typeof EmployeeSelect>> = {}) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  render(
    <QueryClientProvider client={queryClient}>
      <EmployeeSelect value="" onChange={vi.fn()} {...props} />
    </QueryClientProvider>
  );
}

beforeEach(() => vi.clearAllMocks());

describe('EmployeeSelect', () => {
  it('forwards departmentId into the employees list filter when provided', async () => {
    vi.mocked(employeeService.list).mockResolvedValue({
      items: [makeEmployeeResource()],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 100 },
    });

    renderSelect({ departmentId: 'department_1' });

    await waitFor(() =>
      expect(employeeService.list).toHaveBeenCalledWith(
        expect.objectContaining({ department_id: 'department_1' })
      )
    );
  });

  it('leaves the department filter unset when departmentId is omitted (existing unfiltered call sites)', async () => {
    vi.mocked(employeeService.list).mockResolvedValue({
      items: [makeEmployeeResource()],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 100 },
    });

    renderSelect();

    await waitFor(() =>
      expect(employeeService.list).toHaveBeenCalledWith(
        expect.objectContaining({ department_id: undefined })
      )
    );
  });
});
