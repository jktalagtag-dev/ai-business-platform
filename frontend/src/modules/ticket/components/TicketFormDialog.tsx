import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useAbility } from '@/hooks/useAbility';
import { useCreateTicket, useUpdateTicket } from '@/modules/ticket/hooks/useTickets';
import {
  NONE_OPTION,
  ticketPriorityOptions,
  ticketSchema,
  ticketTypeOptions,
  type TicketFormValues,
} from '@/modules/ticket/forms/schemas';
import { EmployeeSelect } from '@/modules/employee/components/EmployeeSelect';
import type { TicketResource } from '@/modules/ticket/types';

const TYPE_LABEL: Record<(typeof ticketTypeOptions)[number], string> = {
  hardware: 'Hardware',
  software: 'Software',
  network: 'Network',
  account_access: 'Account access',
  printer: 'Printer',
  email: 'Email',
  security: 'Security',
  other: 'Other',
};

const PRIORITY_LABEL: Record<(typeof ticketPriorityOptions)[number], string> = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  critical: 'Critical',
};

export function TicketFormDialog({
  open,
  onOpenChange,
  ticket,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** Present when editing; absent when creating. */
  ticket?: TicketResource;
}) {
  const isEditing = !!ticket;
  const canManage = useAbility('tickets.manage');
  const create = useCreateTicket();
  const update = useUpdateTicket(ticket?.id ?? '');
  const mutation = isEditing ? update : create;

  const form = useForm<TicketFormValues>({
    resolver: zodResolver(ticketSchema),
    defaultValues: {
      employee_id: NONE_OPTION,
      type: 'other',
      priority: 'medium',
      subject: '',
      description: '',
      resolution_notes: '',
    },
  });

  useEffect(() => {
    if (open) {
      form.reset({
        employee_id: NONE_OPTION,
        type: ticket?.attributes.ticket_type ?? 'other',
        priority: ticket?.attributes.priority ?? 'medium',
        subject: ticket?.attributes.subject ?? '',
        description: ticket?.attributes.description ?? '',
        resolution_notes: ticket?.attributes.resolution_notes ?? '',
      });
    }
  }, [open, ticket, form]);

  const onSubmit = form.handleSubmit((values) => {
    if (isEditing) {
      update.mutate(
        {
          type: values.type,
          priority: values.priority,
          subject: values.subject,
          description: values.description,
          resolution_notes: values.resolution_notes || null,
        },
        {
          onSuccess: () => {
            toast.success('Ticket updated.');
            onOpenChange(false);
          },
          onError: (error) => {
            if (!applyApiErrorsToForm(error, form.setError)) {
              toast.error(isApiError(error) ? error.message : 'Unable to update ticket.');
            }
          },
        }
      );
      return;
    }

    create.mutate(
      {
        employee_id: values.employee_id === NONE_OPTION ? undefined : values.employee_id,
        type: values.type,
        priority: values.priority,
        subject: values.subject,
        description: values.description,
      },
      {
        onSuccess: () => {
          toast.success('Ticket created.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to create ticket.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Edit ticket' : 'New ticket'}</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            {!isEditing && canManage && (
              <FormField
                control={form.control}
                name="employee_id"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>On behalf of</FormLabel>
                    <FormControl>
                      <EmployeeSelect value={field.value} onChange={field.onChange} placeholder="Myself" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            )}

            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Type</FormLabel>
                    <FormControl>
                      <Select value={field.value} onValueChange={field.onChange}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {ticketTypeOptions.map((opt) => (
                            <SelectItem key={opt} value={opt}>
                              {TYPE_LABEL[opt]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="priority"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Priority</FormLabel>
                    <FormControl>
                      <Select value={field.value} onValueChange={field.onChange}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          {ticketPriorityOptions.map((opt) => (
                            <SelectItem key={opt} value={opt}>
                              {PRIORITY_LABEL[opt]}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="subject"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Subject</FormLabel>
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
                    <Textarea rows={4} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {isEditing && (
              <FormField
                control={form.control}
                name="resolution_notes"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Resolution notes (optional)</FormLabel>
                    <FormControl>
                      <Textarea rows={3} {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            )}

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
