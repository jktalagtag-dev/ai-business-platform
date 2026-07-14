import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageHeader } from '@/components/layout/PageHeader';
import { ProfileForm } from '@/modules/auth/components/ProfileForm';
import { useAuth } from '@/hooks/useAuth';

export function ProfilePage() {
  const { user } = useAuth();

  return (
    <>
      <PageHeader title="Profile" description="Manage your account details." />
      <Card>
        <CardHeader>
          <CardTitle>Account</CardTitle>
        </CardHeader>
        <CardContent>
          <ProfileForm
            defaultName={user?.attributes.name ?? ''}
            defaultEmail={user?.attributes.email ?? ''}
          />
        </CardContent>
      </Card>
    </>
  );
}
