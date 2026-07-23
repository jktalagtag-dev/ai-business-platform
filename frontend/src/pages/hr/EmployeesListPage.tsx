import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { useNavigate } from 'react-router-dom';
import { Plus } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { useAbility } from '@/hooks/useAbility';
import { useDepartments } from '@/modules/employee/hooks/useDepartments';
import { useEmployees } from '@/modules/employee/hooks/useEmployees';
import { CreateEmployeeDialog } from '@/modules/employee/components/CreateEmployeeDialog';
import { paths } from '@/routes/routes.config';
import type { EmployeeListParams, EmployeeResource, EmploymentStatus } from '@/modules/employee/types';

const ALL_DEPARTMENTS = '__all__';
const ALL_STATUS = '__all__';
const SORTABLE_COLUMNS = ['first_name', 'last_name', 'hire_date'];

const STATUS_BADGE: Record<EmploymentStatus, 'success' | 'secondary' | 'warning' | 'destructive'> = {
  active: 'success',
  on_leave: 'warning',
  suspended: 'destructive',
  terminated: 'secondary',
};

export function EmployeesListPage() {
  const navigate = useNavigate();
  const canManage = useAbility('employees.manage');
  const { data: departmentsPage } = useDepartments();

  const [search, setSearch] = useState('');
  const [departmentId, setDepartmentId] = useState(ALL_DEPARTMENTS);
  const [status, setStatus] = useState(ALL_STATUS);
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const [sortBy, setSortBy] = useState<EmployeeListParams['sort']>('last_name');
  const [direction, setDirection] = useState<EmployeeListParams['direction']>('asc');

  const filter: EmployeeListParams = {
    search: search || undefined,
    department_id: departmentId === ALL_DEPARTMENTS ? undefined : departmentId,
    employment_status: status === ALL_STATUS ? undefined : (status as EmploymentStatus),
    sort: sortBy,
    direction,
    cursor,
  };
  const { data, isLoading, isError, refetch } = useEmployees(filter);

  const [createOpen, setCreateOpen] = useState(false);

  const departmentNameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const item of departmentsPage?.items ?? []) map.set(item.id, item.attributes.name);
    return map;
  }, [departmentsPage]);

  function resetToFirstPage<T>(setter: (v: T) => void) {
    return (v: T) => {
      setCursor(undefined);
      setter(v);
    };
  }

  function handleSortChange(columnId: string) {
    setCursor(undefined);
    if (sortBy === columnId) {
      setDirection(direction === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(columnId as EmployeeListParams['sort']);
      setDirection('asc');
    }
  }

  const columns = useMemo<ColumnDef<EmployeeResource, unknown>[]>(
    () => [
      {
        id: 'first_name',
        header: 'Name',
        cell: ({ row }) => (
          <div className="flex items-center gap-2">
            <Avatar className="h-7 w-7">
              <AvatarImage src={row.original.attributes.avatar_url ?? undefined} />
              <AvatarFallback>{row.original.attributes.first_name.charAt(0)}</AvatarFallback>
            </Avatar>
            {row.original.attributes.full_name}
          </div>
        ),
      },
      {
        id: 'employee_number',
        header: 'Employee #',
        cell: ({ row }) => row.original.attributes.employee_number,
      },
      {
        id: 'department',
        header: 'Department',
        cell: ({ row }) => {
          const id = row.original.attributes.department_id;
          if (!id) return <span className="text-muted-foreground">—</span>;
          return departmentNameById.get(id) ?? <span className="text-muted-foreground">—</span>;
        },
      },
      {
        id: 'hire_date',
        header: 'Hire date',
        cell: ({ row }) => row.original.attributes.hire_date,
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => {
          const status = row.original.attributes.employment_status;
          return <Badge variant={STATUS_BADGE[status]}>{status.replace('_', ' ')}</Badge>;
        },
      },
    ],
    [departmentNameById]
  );

  return (
    <div>
      <PageHeader
        title="Employees"
        description="Directory of everyone in your organization."
        actions={
          canManage && (
            <Button onClick={() => setCreateOpen(true)}>
              <Plus className="h-4 w-4" />
              New employee
            </Button>
          )
        }
      />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No employees found"
        onRowClick={(employee) => navigate(`${paths.employees}/${employee.id}`)}
        toolbar={
          <>
            <Input
              placeholder="Search by name, email, or employee #…"
              value={search}
              onChange={(e) => resetToFirstPage(setSearch)(e.target.value)}
              className="sm:max-w-xs"
            />
            <Select value={departmentId} onValueChange={resetToFirstPage(setDepartmentId)}>
              <SelectTrigger className="sm:w-48">
                <SelectValue placeholder="All departments" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_DEPARTMENTS}>All departments</SelectItem>
                {(departmentsPage?.items ?? []).map((d) => (
                  <SelectItem key={d.id} value={d.id}>
                    {d.attributes.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={status} onValueChange={resetToFirstPage(setStatus)}>
              <SelectTrigger className="sm:w-40">
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_STATUS}>All statuses</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="on_leave">On leave</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
                <SelectItem value="terminated">Terminated</SelectItem>
              </SelectContent>
            </Select>
          </>
        }
        sorting={{
          sortBy: sortBy ?? 'last_name',
          direction: direction ?? 'asc',
          sortableColumns: SORTABLE_COLUMNS,
          onSortChange: handleSortChange,
        }}
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && <CreateEmployeeDialog open={createOpen} onOpenChange={setCreateOpen} />}
    </div>
  );
}
