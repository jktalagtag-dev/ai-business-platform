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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
import { useCreateSupplier, useUpdateSupplier } from '@/modules/inventory/hooks/useSuppliers';
import { supplierSchema, type SupplierFormValues } from '@/modules/inventory/forms/schemas';
import type { SupplierResource } from '@/modules/inventory/types';

export function SupplierFormDialog({
  open,
  onOpenChange,
  supplier,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  /** Present when editing; absent when creating. */
  supplier?: SupplierResource;
}) {
  const isEditing = !!supplier;
  const create = useCreateSupplier();
  const update = useUpdateSupplier(supplier?.id ?? '');
  const mutation = isEditing ? update : create;

  const form = useForm<SupplierFormValues>({
    resolver: zodResolver(supplierSchema),
    defaultValues: {
      name: '',
      contact_email: '',
      contact_phone: '',
      status: 'active',
      address_line1: '',
      address_city: '',
      address_state: '',
      address_postal_code: '',
      address_country: '',
    },
  });

  useEffect(() => {
    if (open) {
      const address = supplier?.attributes.address ?? undefined;
      form.reset({
        name: supplier?.attributes.name ?? '',
        contact_email: supplier?.attributes.contact_email ?? '',
        contact_phone: supplier?.attributes.contact_phone ?? '',
        status: supplier?.attributes.status ?? 'active',
        address_line1: address?.line1 ?? '',
        address_city: address?.city ?? '',
        address_state: address?.state ?? '',
        address_postal_code: address?.postal_code ?? '',
        address_country: address?.country ?? '',
      });
    }
  }, [open, supplier, form]);

  const onSubmit = form.handleSubmit((values) => {
    const address = {
      line1: values.address_line1 || undefined,
      city: values.address_city || undefined,
      state: values.address_state || undefined,
      postal_code: values.address_postal_code || undefined,
      country: values.address_country || undefined,
    };
    const hasAddress = Object.values(address).some((v) => v !== undefined);

    mutation.mutate(
      {
        name: values.name,
        contact_email: values.contact_email || null,
        contact_phone: values.contact_phone || null,
        status: values.status,
        address: hasAddress ? address : null,
      },
      {
        onSuccess: () => {
          toast.success(isEditing ? 'Supplier updated.' : 'Supplier created.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to save supplier.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{isEditing ? 'Edit supplier' : 'New supplier'}</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem className="col-span-2">
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
                name="contact_email"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Contact email</FormLabel>
                    <FormControl>
                      <Input type="email" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="contact_phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Contact phone</FormLabel>
                    <FormControl>
                      <Input {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

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
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="inactive">Inactive</SelectItem>
                      </SelectContent>
                    </Select>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <fieldset className="space-y-4 rounded-md border p-3">
              <legend className="px-1 text-sm font-medium text-muted-foreground">
                Address (optional)
              </legend>
              <FormField
                control={form.control}
                name="address_line1"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Street</FormLabel>
                    <FormControl>
                      <Input {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="address_city"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>City</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="address_state"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>State</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="address_postal_code"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Postal code</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="address_country"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Country</FormLabel>
                      <FormControl>
                        <Input {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            </fieldset>

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
