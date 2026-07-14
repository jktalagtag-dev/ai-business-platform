import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useCreateCategory, useUpdateCategory } from '@/modules/inventory/hooks/useCategories';
import { categorySchema, NONE_CATEGORY, type CategoryFormValues } from '@/modules/inventory/forms/schemas';
import { CategorySelect } from '@/modules/inventory/components/CategorySelect';
import type { CategoryResource } from '@/modules/inventory/types';

export function CategoryFormDialog({
  open,
  onOpenChange,
  category,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** Present when editing; absent when creating. */
  category?: CategoryResource;
}) {
  const isEditing = !!category;
  const create = useCreateCategory();
  const update = useUpdateCategory(category?.id ?? '');
  const mutation = isEditing ? update : create;

  const form = useForm<CategoryFormValues>({
    resolver: zodResolver(categorySchema),
    defaultValues: { name: '', parent_category_id: NONE_CATEGORY },
  });

  useEffect(() => {
    if (open) {
      form.reset({
        name: category?.attributes.name ?? '',
        parent_category_id: category?.attributes.parent_category_id ?? NONE_CATEGORY,
      });
    }
  }, [open, category, form]);

  const onSubmit = form.handleSubmit((values) => {
    mutation.mutate(
      {
        name: values.name,
        parent_category_id:
          values.parent_category_id === NONE_CATEGORY ? null : values.parent_category_id,
      },
      {
        onSuccess: () => {
          toast.success(isEditing ? 'Category updated.' : 'Category created.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to save category.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Edit category' : 'New category'}</DialogTitle>
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
              name="parent_category_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Parent category</FormLabel>
                  <FormControl>
                    <CategorySelect
                      value={field.value}
                      onChange={field.onChange}
                      excludeId={category?.id}
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
