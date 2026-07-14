import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Form } from '@/components/ui/form';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/components/layout/EmptyState';
import { ErrorState } from '@/components/layout/ErrorState';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useAbility } from '@/hooks/useAbility';
import { useMyEmployeeProfile, useUpdateEmployee } from '@/modules/employee/hooks/useEmployees';
import { employeeSchema, type EmployeeFormValues } from '@/modules/employee/forms/schemas';
import { employeeFormValuesToPayload, employeeResourceToFormValues } from '@/modules/employee/forms/mapValuesToPayload';
import { EmployeeFormFields } from '@/modules/employee/components/EmployeeFormFields';
import { EmployeeAvatarUploader } from '@/modules/employee/components/EmployeeAvatarUploader';

/**
 * Self-service employee profile (GET/PATCH /employees/me), distinct from the
 * account-level Settings > Profile (name/email login credentials). Reachable
 * by any authenticated member — a 404 here just means their account has no
 * linked employee record, which is a normal outcome, not an error.
 */
export function MyEmployeeProfilePage() {
  const canManage = useAbility('employees.manage');
  const { data: employee, isLoading, isError, error, refetch } = useMyEmployeeProfile();
  const notLinked = isError && isApiError(error) && error.status === 404;
  const update = useUpdateEmployee(employee?.id ?? '');

  const form = useForm<EmployeeFormValues>({
    resolver: zodResolver(employeeSchema),
    defaultValues: employeeResourceToFormValues(employee),
  });

  useEffect(() => {
    if (employee) form.reset(employeeResourceToFormValues(employee));
  }, [employee, form]);

  const onSubmit = form.handleSubmit((values) => {
    update.mutate(employeeFormValuesToPayload(values), {
      onSuccess: () => toast.success('Profile updated.'),
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to update your profile.');
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

  if (notLinked) {
    return (
      <div>
        <PageHeader title="My Profile" />
        <EmptyState
          title="No employee profile linked to your account"
          description="Ask an HR administrator to link your account to an employee record."
        />
      </div>
    );
  }

  if (isError || !employee) {
    return (
      <div>
        <PageHeader title="My Profile" />
        <ErrorState message="Failed to load your profile." onRetry={() => refetch()} />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="My Profile" description={employee.attributes.employee_number} />

      <EmployeeAvatarUploader employee={employee} canEdit />

      <Form {...form}>
        <form onSubmit={onSubmit} className="max-w-2xl space-y-4" noValidate>
          <EmployeeFormFields control={form.control} canManage={canManage} mode="edit" excludeEmployeeId={employee.id} />
          <div className="flex justify-end">
            <Button type="submit" disabled={update.isPending || !form.formState.isDirty}>
              {update.isPending && <Loader2 className="animate-spin" />}
              Save changes
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
}
