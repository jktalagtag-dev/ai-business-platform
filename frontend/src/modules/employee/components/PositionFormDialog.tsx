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
import { useCreatePosition, useUpdatePosition } from '@/modules/employee/hooks/usePositions';
import { positionSchema, type PositionFormValues } from '@/modules/employee/forms/schemas';
import type { PositionResource } from '@/modules/employee/types';

export function PositionFormDialog({
  open,
  onOpenChange,
  position,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  position?: PositionResource;
}) {
  const isEditing = !!position;
  const create = useCreatePosition();
  const update = useUpdatePosition(position?.id ?? '');
  const mutation = isEditing ? update : create;

  const form = useForm<PositionFormValues>({
    resolver: zodResolver(positionSchema),
    defaultValues: { title: '', description: '' },
  });

  useEffect(() => {
    if (open) {
      form.reset({
        title: position?.attributes.title ?? '',
        description: position?.attributes.description ?? '',
      });
    }
  }, [open, position, form]);

  const onSubmit = form.handleSubmit((values) => {
    mutation.mutate(
      { title: values.title, description: values.description || null },
      {
        onSuccess: () => {
          toast.success(isEditing ? 'Position updated.' : 'Position created.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to save position.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Edit position' : 'New position'}</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <FormField
              control={form.control}
              name="title"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Title</FormLabel>
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
