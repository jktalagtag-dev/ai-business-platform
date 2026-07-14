import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { useUpdateProfile } from '@/modules/auth/hooks/useProfile';
import { updateProfileSchema, type UpdateProfileFormValues } from '@/modules/auth/forms/schemas';

export function ProfileForm({ defaultName, defaultEmail }: { defaultName: string; defaultEmail: string }) {
  const update = useUpdateProfile();
  const form = useForm<UpdateProfileFormValues>({
    resolver: zodResolver(updateProfileSchema),
    values: { name: defaultName, email: defaultEmail },
  });

  const onSubmit = form.handleSubmit((values) => {
    update.mutate(values, {
      onSuccess: () => toast.success('Profile updated.'),
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to update profile.');
        }
      },
    });
  });

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} className="max-w-md space-y-4" noValidate>
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input autoComplete="name" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input type="email" autoComplete="email" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <Button type="submit" disabled={update.isPending || !form.formState.isDirty}>
          {update.isPending && <Loader2 className="animate-spin" />}
          Save changes
        </Button>
      </form>
    </Form>
  );
}
