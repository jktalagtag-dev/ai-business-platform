import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useCloseTicket } from '@/modules/ticket/hooks/useTicketActions';
import { closeTicketSchema, type CloseTicketFormValues } from '@/modules/ticket/forms/schemas';

export function CloseTicketDialog({
  open,
  onOpenChange,
  ticketId,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  ticketId: string;
}) {
  const close = useCloseTicket(ticketId);

  const form = useForm<CloseTicketFormValues>({
    resolver: zodResolver(closeTicketSchema),
    defaultValues: { resolution_notes: '' },
  });

  useEffect(() => {
    if (open) form.reset({ resolution_notes: '' });
  }, [open, form]);

  const onSubmit = form.handleSubmit((values) => {
    close.mutate(values, {
      onSuccess: () => {
        toast.success('Ticket closed.');
        onOpenChange(false);
      },
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to close ticket.');
        }
      },
    });
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Close ticket</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <FormField
              control={form.control}
              name="resolution_notes"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Resolution notes</FormLabel>
                  <FormControl>
                    <Textarea rows={3} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={close.isPending}>
                {close.isPending && <Loader2 className="animate-spin" />}
                Close ticket
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
