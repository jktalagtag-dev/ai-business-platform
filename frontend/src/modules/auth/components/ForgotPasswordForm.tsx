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
import { useForgotPassword } from '@/modules/auth/hooks/usePasswordReset';
import {
  forgotPasswordSchema,
  type ForgotPasswordFormValues,
} from '@/modules/auth/forms/schemas';

export function ForgotPasswordForm({ onSent }: { onSent: (message: string) => void }) {
  const forgot = useForgotPassword();
  const form = useForm<ForgotPasswordFormValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  });

  const onSubmit = form.handleSubmit((values) => {
    forgot.mutate(values, {
      onSuccess: (result) => onSent(result.message),
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Something went wrong. Please try again.');
        }
      },
    });
  });

  return (
    <Form {...form}>
      <form onSubmit={onSubmit} className="space-y-4" noValidate>
        <FormField
          control={form.control}
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input type="email" autoComplete="email" placeholder="you@example.com" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type="submit" className="w-full" disabled={forgot.isPending}>
          {forgot.isPending && <Loader2 className="animate-spin" />}
          Send reset link
        </Button>
      </form>
    </Form>
  );
}
