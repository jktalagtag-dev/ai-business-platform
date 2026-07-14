import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ForgotPasswordForm } from '@/modules/auth/components/ForgotPasswordForm';
import { paths } from '@/routes/routes.config';

export function ForgotPasswordPage() {
  const [sentMessage, setSentMessage] = useState<string | null>(null);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Reset your password</CardTitle>
        <CardDescription>
          Enter your email and we'll send a reset link if an account exists.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        {sentMessage ? (
          <p className="rounded-md bg-muted p-3 text-sm text-muted-foreground">{sentMessage}</p>
        ) : (
          <ForgotPasswordForm onSent={setSentMessage} />
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
