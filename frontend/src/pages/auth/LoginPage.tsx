import { Link } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { LoginForm } from '@/modules/auth/components/LoginForm';
import { paths } from '@/routes/routes.config';

export function LoginPage() {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Sign in</CardTitle>
        <CardDescription>Welcome back. Enter your credentials to continue.</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <LoginForm />
        <div className="flex items-center justify-between text-sm">
          <Link to={paths.forgotPassword} className="text-primary hover:underline">
            Forgot password?
          </Link>
          <Link to={paths.register} className="text-primary hover:underline">
            Create an account
          </Link>
        </div>
      </CardContent>
    </Card>
  );
}
