import { LandingHeader } from '@/pages/marketing/sections/LandingHeader';
import { HeroSection } from '@/pages/marketing/sections/HeroSection';

/**
 * Public marketing page shown at `/` to unauthenticated visitors (see
 * RequireAuth) — authenticated users get the dashboard at the same path
 * instead. Deliberately outside AppLayout: no sidebar/topbar chrome, just
 * this page's own header and sections, added phase by phase.
 */
export function LandingPage() {
  return (
    <div className="min-h-svh bg-background pb-24">
      <LandingHeader />
      <main>
        <HeroSection />
      </main>
    </div>
  );
}
