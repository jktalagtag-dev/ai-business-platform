import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate } from 'react-router-dom';
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
import { paths } from '@/routes/routes.config';
import { useResetPassword } from '@/modules/auth/hooks/usePasswordReset';
import { resetPasswordSchema, type ResetPasswordFormValues } from '@/modules/auth/forms/schemas';

interface ResetPasswordFormProps {
  /** Prefilled from the reset link's query string. */
  defaultEmail: string;
  defaultToken: string;
}

export function ResetPasswordForm({ defaultEmail, defaultToken }: ResetPasswordFormProps) {
  const navigate = useNavigate();
  const reset = useResetPassword();

  const form = useForm<ResetPasswordFormValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      email: defaultEmail,
      token: defaultToken,
      password: '',
      password_confirmation: '',
    },
  });

  const onSubmit = form.handleSubmit((values) => {
    reset.mutate(values, {
      onSuccess: () => {
        toast.success('Your password has been reset. Please sign in.');
        navigate(paths.login, { replace: true });
      },
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to reset password.');
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
                <Input type="email" autoComplete="email" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>New password</FormLabel>
              <FormControl>
                <Input type="password" autoComplete="new-password" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="password_confirmation"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Confirm new password</FormLabel>
              <FormControl>
                <Input type="password" autoComplete="new-password" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <Button type="submit" className="w-full" disabled={reset.isPending}>
          {reset.isPending && <Loader2 className="animate-spin" />}
          Reset password
        </Button>
      </form>
    </Form>
  );
}
