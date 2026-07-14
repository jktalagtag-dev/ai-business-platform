import { useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useParams } from 'react-router-dom';
import { Loader2, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Form } from '@/components/ui/form';
import { PageHeader } from '@/components/layout/PageHeader';
import { ErrorState } from '@/components/layout/ErrorState';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useAbility } from '@/hooks/useAbility';
import { useDepartments } from '@/modules/employee/hooks/useDepartments';
import { usePositions } from '@/modules/employee/hooks/usePositions';
import {
  useDeleteEmployee,
  useEmployee,
  useMyEmployeeProfile,
  useUpdateEmployee,
} from '@/modules/employee/hooks/useEmployees';
import { employeeSchema, type EmployeeFormValues } from '@/modules/employee/forms/schemas';
import { employeeFormValuesToPayload, employeeResourceToFormValues } from '@/modules/employee/forms/mapValuesToPayload';
import { EmployeeFormFields } from '@/modules/employee/components/EmployeeFormFields';
import { EmployeeAvatarUploader } from '@/modules/employee/components/EmployeeAvatarUploader';
import { EmployeeNotesPanel } from '@/modules/employee/components/EmployeeNotesPanel';
import { paths } from '@/routes/routes.config';

const STATUS_BADGE: Record<string, 'success' | 'secondary' | 'warning' | 'destructive'> = {
  active: 'success',
  on_leave: 'warning',
  suspended: 'destructive',
  terminated: 'secondary',
};

export function EmployeeDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const canManage = useAbility('employees.manage');

  const { data: employee, isLoading, isError, refetch } = useEmployee(id);
  const { data: myProfile } = useMyEmployeeProfile();
  const isSelf = !!id && myProfile?.id === id;
  const canEdit = canManage || isSelf;

  const { data: departmentsPage } = useDepartments();
  const { data: positionsPage } = usePositions();
  const departmentName = useMemo(
    () => new Map((departmentsPage?.items ?? []).map((d) => [d.id, d.attributes.name])),
    [departmentsPage]
  );
  const positionName = useMemo(
    () => new Map((positionsPage?.items ?? []).map((p) => [p.id, p.attributes.title])),
    [positionsPage]
  );

  const update = useUpdateEmployee(id ?? '');
  const deleteEmployee = useDeleteEmployee();
  const [deleteOpen, setDeleteOpen] = useState(false);

  const form = useForm<EmployeeFormValues>({
    resolver: zodResolver(employeeSchema),
    defaultValues: employeeResourceToFormValues(employee),
  });

  useEffect(() => {
    if (employee) form.reset(employeeResourceToFormValues(employee));
  }, [employee, form]);

  const onSubmit = form.handleSubmit((values) => {
    update.mutate(employeeFormValuesToPayload(values), {
      onSuccess: () => toast.success('Employee updated.'),
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to update employee.');
        }
      },
    });
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center rounded-lg border p-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (isError || !employee) {
    return <ErrorState message="Employee not found." onRetry={() => refetch()} />;
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={employee.attributes.full_name}
        description={employee.attributes.employee_number}
        actions={
          canManage && (
            <Button variant="destructive" onClick={() => setDeleteOpen(true)}>
              <Trash2 className="h-4 w-4" />
              Archive
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="flex flex-wrap items-center gap-6 pt-6">
          <EmployeeAvatarUploader employee={employee} canEdit={canEdit} />
          <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
            {employee.attributes.department_id && (
              <span>{departmentName.get(employee.attributes.department_id)}</span>
            )}
            {employee.attributes.position_id && (
              <span>· {positionName.get(employee.attributes.position_id)}</span>
            )}
            <Badge variant={STATUS_BADGE[employee.attributes.employment_status]}>
              {employee.attributes.employment_status.replace('_', ' ')}
            </Badge>
          </div>
        </CardContent>
      </Card>

      {canEdit ? (
        <Form {...form}>
          <form onSubmit={onSubmit} className="max-w-2xl space-y-4" noValidate>
            <EmployeeFormFields
              control={form.control}
              canManage={canManage}
              mode="edit"
              excludeEmployeeId={employee.id}
            />
            <div className="flex justify-end">
              <Button type="submit" disabled={update.isPending || !form.formState.isDirty}>
                {update.isPending && <Loader2 className="animate-spin" />}
                Save changes
              </Button>
            </div>
          </form>
        </Form>
      ) : (
        <Card>
          <CardContent className="grid grid-cols-2 gap-4 pt-6 text-sm">
            <div>
              <div className="text-muted-foreground">Email</div>
              <div>{employee.attributes.email ?? '—'}</div>
            </div>
            <div>
              <div className="text-muted-foreground">Phone</div>
              <div>{employee.attributes.phone ?? '—'}</div>
            </div>
            <div>
              <div className="text-muted-foreground">Hire date</div>
              <div>{employee.attributes.hire_date}</div>
            </div>
            <div>
              <div className="text-muted-foreground">Employment type</div>
              <div>{employee.attributes.employment_type.replace('_', ' ')}</div>
            </div>
            {employee.attributes.bio && (
              <div className="col-span-2">
                <div className="text-muted-foreground">Bio</div>
                <div className="whitespace-pre-wrap">{employee.attributes.bio}</div>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {canManage && (
        <div>
          <h2 className="mb-3 text-lg font-semibold">Notes</h2>
          <EmployeeNotesPanel employeeId={employee.id} />
        </div>
      )}

      {canManage && (
        <ConfirmDialog
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          title="Archive employee?"
          description={`"${employee.attributes.full_name}" will be archived and removed from active lists.`}
          confirmLabel="Archive"
          isLoading={deleteEmployee.isPending}
          onConfirm={() => {
            deleteEmployee.mutate(employee.id, {
              onSuccess: () => {
                toast.success('Employee archived.');
                navigate(paths.employees);
              },
              onError: () => toast.error('Unable to archive employee.'),
            });
          }}
        />
      )}
    </div>
  );
}
