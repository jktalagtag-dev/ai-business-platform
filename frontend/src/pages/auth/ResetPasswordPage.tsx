import { Link, useSearchParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ResetPasswordForm } from '@/modules/auth/components/ResetPasswordForm';
import { paths } from '@/routes/routes.config';

export function ResetPasswordPage() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const email = searchParams.get('email') ?? '';

  return (
    <Card>
      <CardHeader>
        <CardTitle>Choose a new password</CardTitle>
        <CardDescription>Enter and confirm your new password below.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {token ? (
          <ResetPasswordForm defaultEmail={email} defaultToken={token} />
        ) : (
          <p className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
            This reset link is missing its token. Please request a new link from the{' '}
            <Link to={paths.forgotPassword} className="text-primary hover:underline">
              forgot password
            </Link>{' '}
            page.
          </p>
        )}
        <p className="text-center text-sm">
          <Link to={paths.login} className="text-primary hover:underline">
            Back to sign in
          </Link>
        </p>
      </CardContent>
    </Card>
  );
}
