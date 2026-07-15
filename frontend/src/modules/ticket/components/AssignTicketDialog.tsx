import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useAssignTicket } from '@/modules/ticket/hooks/useTicketActions';
import { assignTicketSchema, type AssignTicketFormValues } from '@/modules/ticket/forms/schemas';
import { EmployeeSelect } from '@/modules/employee/components/EmployeeSelect';
import type { TicketResource } from '@/modules/ticket/types';

export function AssignTicketDialog({
  open,
  onOpenChange,
  ticket,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  ticket: TicketResource;
}) {
  const assign = useAssignTicket(ticket.id);

  const form = useForm<AssignTicketFormValues>({
    resolver: zodResolver(assignTicketSchema),
    defaultValues: { technician_employee_id: ticket.attributes.assigned_technician_id ?? '' },
  });

  useEffect(() => {
    if (open) form.reset({ technician_employee_id: ticket.attributes.assigned_technician_id ?? '' });
  }, [open, ticket, form]);

  const onSubmit = form.handleSubmit((values) => {
    assign.mutate(values, {
      onSuccess: () => {
        toast.success('Ticket assigned.');
        onOpenChange(false);
      },
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to assign ticket.');
        }
      },
    });
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Assign ticket</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <FormField
              control={form.control}
              name="technician_employee_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Technician</FormLabel>
                  <FormControl>
                    <EmployeeSelect
                      value={field.value}
                      onChange={field.onChange}
                      placeholder="Choose…"
                      departmentId={ticket.attributes.department_id ?? undefined}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={assign.isPending}>
                {assign.isPending && <Loader2 className="animate-spin" />}
                Assign
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
