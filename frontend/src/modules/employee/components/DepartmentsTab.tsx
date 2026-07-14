import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { useAbility } from '@/hooks/useAbility';
import { useEmployees } from '@/modules/employee/hooks/useEmployees';
import { useDeleteDepartment, useDepartments } from '@/modules/employee/hooks/useDepartments';
import { DepartmentFormDialog } from '@/modules/employee/components/DepartmentFormDialog';
import type { DepartmentResource } from '@/modules/employee/types';

export function DepartmentsTab() {
  const canManage = useAbility('departments.manage');
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useDepartments(cursor);
  const { data: employeesPage } = useEmployees({ per_page: 100 });

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<DepartmentResource | undefined>(undefined);
  const [deleting, setDeleting] = useState<DepartmentResource | undefined>(undefined);
  const deleteDepartment = useDeleteDepartment();

  const nameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const item of data?.items ?? []) map.set(item.id, item.attributes.name);
    return map;
  }, [data]);

  const employeeNameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const item of employeesPage?.items ?? []) map.set(item.id, item.attributes.full_name);
    return map;
  }, [employeesPage]);

  const columns = useMemo<ColumnDef<DepartmentResource, unknown>[]>(
    () => [
      { id: 'name', header: 'Name', cell: ({ row }) => row.original.attributes.name },
      {
        id: 'parent',
        header: 'Parent',
        cell: ({ row }) => {
          const id = row.original.attributes.parent_department_id;
          if (!id) return <span className="text-muted-foreground">—</span>;
          return nameById.get(id) ?? <span className="text-muted-foreground">—</span>;
        },
      },
      {
        id: 'manager',
        header: 'Manager',
        cell: ({ row }) => {
          const id = row.original.attributes.manager_employee_id;
          if (!id) return <span className="text-muted-foreground">—</span>;
          return employeeNameById.get(id) ?? <span className="text-muted-foreground">—</span>;
        },
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: DepartmentResource } }) => (
                <div className="flex justify-end gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                      setEditing(row.original);
                      setFormOpen(true);
                    }}
                  >
                    <Pencil className="h-4 w-4" />
                    <span className="sr-only">Edit</span>
                  </Button>
                  <Button variant="ghost" size="icon" onClick={() => setDeleting(row.original)}>
                    <Trash2 className="h-4 w-4" />
                    <span className="sr-only">Delete</span>
                  </Button>
                </div>
              ),
            } satisfies ColumnDef<DepartmentResource, unknown>,
          ]
        : []),
    ],
    [canManage, nameById, employeeNameById]
  );

  return (
    <div>
      <PageHeader
        title="Departments"
        actions={
          canManage && (
            <Button
              onClick={() => {
                setEditing(undefined);
                setFormOpen(true);
              }}
            >
              <Plus className="h-4 w-4" />
              New department
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
        emptyTitle="No departments yet"
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <>
          <DepartmentFormDialog open={formOpen} onOpenChange={setFormOpen} department={editing} />
          <ConfirmDialog
            open={!!deleting}
            onOpenChange={(open) => !open && setDeleting(undefined)}
            title="Delete department?"
            description={`"${deleting?.attributes.name}" will be permanently removed.`}
            confirmLabel="Delete"
            isLoading={deleteDepartment.isPending}
            onConfirm={() => {
              if (!deleting) return;
              deleteDepartment.mutate(deleting.id, {
                onSuccess: () => {
                  toast.success('Department deleted.');
                  setDeleting(undefined);
                },
                onError: () => toast.error('Unable to delete department.'),
              });
            }}
          />
        </>
      )}
    </div>
  );
}
