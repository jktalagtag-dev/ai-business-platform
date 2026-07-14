import { useState } from 'react';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { paths } from '@/routes/routes.config';
import { useLogin } from '@/modules/auth/hooks/useLogin';
import { loginSchema, type LoginFormValues } from '@/modules/auth/forms/schemas';
import type { AvailableTenant } from '@/modules/auth/types';

export function LoginForm() {
  const navigate = useNavigate();
  const login = useLogin();
  const [availableTenants, setAvailableTenants] = useState<AvailableTenant[] | null>(null);

  const form = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '', tenant_slug: undefined },
  });

  const onSubmit = form.handleSubmit((values) => {
    login.mutate(values, {
      onSuccess: () => navigate(paths.dashboard, { replace: true }),
      onError: (error) => {
        if (isApiError(error) && error.isTenantAmbiguous()) {
          setAvailableTenants(error.context.available_tenants as AvailableTenant[]);
          toast.info('This account belongs to multiple organizations — pick one to continue.');
          return;
        }
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to sign in. Please try again.');
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

        <FormField
          control={form.control}
          name="password"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <Input type="password" autoComplete="current-password" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {availableTenants && (
          <FormField
            control={form.control}
            name="tenant_slug"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Organization</FormLabel>
                <Select onValueChange={field.onChange} value={field.value}>
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Select an organization" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    {availableTenants.map((tenant) => (
                      <SelectItem key={tenant.slug} value={tenant.slug}>
                        {tenant.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <FormMessage />
              </FormItem>
            )}
          />
        )}

        <Button type="submit" className="w-full" disabled={login.isPending}>
          {login.isPending && <Loader2 className="animate-spin" />}
          Sign in
        </Button>
      </form>
    </Form>
  );
}
