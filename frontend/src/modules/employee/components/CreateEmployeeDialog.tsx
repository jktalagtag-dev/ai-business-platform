import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useCreateEmployee } from '@/modules/employee/hooks/useEmployees';
import { employeeSchema, type EmployeeFormValues } from '@/modules/employee/forms/schemas';
import { employeeFormValuesToPayload, employeeResourceToFormValues } from '@/modules/employee/forms/mapValuesToPayload';
import { EmployeeFormFields } from '@/modules/employee/components/EmployeeFormFields';

export function CreateEmployeeDialog({
  open,
  onOpenChange,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const create = useCreateEmployee();

  const form = useForm<EmployeeFormValues>({
    resolver: zodResolver(employeeSchema),
    defaultValues: employeeResourceToFormValues(),
  });

  useEffect(() => {
    if (open) form.reset(employeeResourceToFormValues());
  }, [open, form]);

  const onSubmit = form.handleSubmit((values) => {
    // termination_date isn't in the Store Form Request's rule set — Laravel's
    // validated() silently drops it, so sending it here is harmless.
    create.mutate(employeeFormValuesToPayload(values), {
      onSuccess: () => {
        toast.success('Employee created.');
        onOpenChange(false);
      },
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to create employee.');
        }
      },
    });
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>New employee</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <EmployeeFormFields control={form.control} canManage mode="create" />

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={create.isPending}>
                {create.isPending && <Loader2 className="animate-spin" />}
                Save
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
