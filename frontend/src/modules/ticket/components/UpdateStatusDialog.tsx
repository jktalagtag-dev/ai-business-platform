import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useUpdateTicketStatus } from '@/modules/ticket/hooks/useTicketActions';
import {
  settableTicketStatusOptions,
  updateTicketStatusSchema,
  type UpdateTicketStatusFormValues,
} from '@/modules/ticket/forms/schemas';
import type { TicketResource } from '@/modules/ticket/types';

const STATUS_LABEL: Record<(typeof settableTicketStatusOptions)[number], string> = {
  open: 'Open',
  assigned: 'Assigned',
  in_progress: 'In progress',
  waiting_for_user: 'Waiting for user',
  resolved: 'Resolved',
  cancelled: 'Cancelled',
};

export function UpdateStatusDialog({
  open,
  onOpenChange,
  ticket,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  ticket: TicketResource;
}) {
  const updateStatus = useUpdateTicketStatus(ticket.id);

  const form = useForm<UpdateTicketStatusFormValues>({
    resolver: zodResolver(updateTicketStatusSchema),
    defaultValues: { status: 'open', note: '' },
  });

  useEffect(() => {
    if (open) {
      const current = ticket.attributes.status;
      form.reset({
        status: current === 'closed' ? 'resolved' : current,
        note: '',
      });
    }
  }, [open, ticket, form]);

  const onSubmit = form.handleSubmit((values) => {
    updateStatus.mutate(
      { status: values.status, note: values.note || null },
      {
        onSuccess: () => {
          toast.success('Status updated.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to update status.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update status</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <FormField
              control={form.control}
              name="status"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Status</FormLabel>
                  <FormControl>
                    <Select value={field.value} onValueChange={field.onChange}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {settableTicketStatusOptions.map((opt) => (
                          <SelectItem key={opt} value={opt}>
                            {STATUS_LABEL[opt]}
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
              name="note"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Note (optional)</FormLabel>
                  <FormControl>
                    <Textarea rows={2} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={updateStatus.isPending}>
                {updateStatus.isPending && <Loader2 className="animate-spin" />}
                Update
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
