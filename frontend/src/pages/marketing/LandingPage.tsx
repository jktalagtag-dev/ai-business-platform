import { LandingHeader } from '@/pages/marketing/sections/LandingHeader';
import { HeroSection } from '@/pages/marketing/sections/HeroSection';
import { ModulesSection } from '@/pages/marketing/sections/ModulesSection';
import { AutomationSection } from '@/pages/marketing/sections/AutomationSection';
import { AiAssistantSection } from '@/pages/marketing/sections/AiAssistantSection';
import { AnalyticsSection } from '@/pages/marketing/sections/AnalyticsSection';

/**
 * Public marketing page shown at `/` to unauthenticated visitors (see
 * RequireAuth) — authenticated users get the dashboard at the same path
 * instead. Deliberately outside AppLayout: no sidebar/topbar chrome, just
 * this page's own header and sections, added phase by phase.
 *
 * `space-y-*` (rather than padding on each section) gives the ~120px gap
 * DESIGN_SYSTEM.md calls for between sections without doubling it at each
 * boundary the way top+bottom padding on adjacent sections would.
 */
export function LandingPage() {
  return (
    <div className="min-h-svh bg-background pb-24">
      <LandingHeader />
      <main className="space-y-16 sm:space-y-24 lg:space-y-[120px]">
        <HeroSection />
        <ModulesSection />
        <AutomationSection />
        <AiAssistantSection />
        <AnalyticsSection />
      </main>
    </div>
  );
}
