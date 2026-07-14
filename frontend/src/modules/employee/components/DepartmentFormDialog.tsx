import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useCreateDepartment, useUpdateDepartment } from '@/modules/employee/hooks/useDepartments';
import { departmentSchema, NONE_OPTION, type DepartmentFormValues } from '@/modules/employee/forms/schemas';
import { DepartmentSelect } from '@/modules/employee/components/DepartmentSelect';
import { EmployeeSelect } from '@/modules/employee/components/EmployeeSelect';
import type { DepartmentResource } from '@/modules/employee/types';

export function DepartmentFormDialog({
  open,
  onOpenChange,
  department,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  department?: DepartmentResource;
}) {
  const isEditing = !!department;
  const create = useCreateDepartment();
  const update = useUpdateDepartment(department?.id ?? '');
  const mutation = isEditing ? update : create;

  const form = useForm<DepartmentFormValues>({
    resolver: zodResolver(departmentSchema),
    defaultValues: { name: '', description: '', parent_department_id: NONE_OPTION, manager_employee_id: NONE_OPTION },
  });

  useEffect(() => {
    if (open) {
      form.reset({
        name: department?.attributes.name ?? '',
        description: department?.attributes.description ?? '',
        parent_department_id: department?.attributes.parent_department_id ?? NONE_OPTION,
        manager_employee_id: department?.attributes.manager_employee_id ?? NONE_OPTION,
      });
    }
  }, [open, department, form]);

  const onSubmit = form.handleSubmit((values) => {
    mutation.mutate(
      {
        name: values.name,
        description: values.description || null,
        parent_department_id:
          values.parent_department_id === NONE_OPTION ? null : values.parent_department_id,
        manager_employee_id:
          values.manager_employee_id === NONE_OPTION ? null : values.manager_employee_id,
      },
      {
        onSuccess: () => {
          toast.success(isEditing ? 'Department updated.' : 'Department created.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to save department.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Edit department' : 'New department'}</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Name</FormLabel>
                  <FormControl>
                    <Input {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description</FormLabel>
                  <FormControl>
                    <Textarea rows={2} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="parent_department_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Parent department</FormLabel>
                  <FormControl>
                    <DepartmentSelect value={field.value} onChange={field.onChange} excludeId={department?.id} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="manager_employee_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Manager</FormLabel>
                  <FormControl>
                    <EmployeeSelect value={field.value} onChange={field.onChange} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={mutation.isPending}>
                {mutation.isPending && <Loader2 className="animate-spin" />}
                Save
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
