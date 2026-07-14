import { Construction } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { EmptyState } from '@/components/layout/EmptyState';

/**
 * Placeholder for modules whose backend exists but whose frontend slice
 * hasn't been built yet. Replaced module-by-module as each slice ships.
 */
export function ComingSoonPage({ title = 'Coming soon' }: { title?: string }) {
  return (
    <>
      <PageHeader title={title} />
      <EmptyState
        icon={Construction}
        title="This module isn't built yet"
        description="The backend is ready; the frontend for this area will be added in an upcoming slice."
      />
    </>
  );
}
