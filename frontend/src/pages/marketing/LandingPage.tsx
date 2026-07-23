import { LandingHeader } from '@/pages/marketing/sections/LandingHeader';
import { HeroSection } from '@/pages/marketing/sections/HeroSection';
import { ModulesSection } from '@/pages/marketing/sections/ModulesSection';
import { AutomationSection } from '@/pages/marketing/sections/AutomationSection';
import { AiAssistantSection } from '@/pages/marketing/sections/AiAssistantSection';
import { AnalyticsSection } from '@/pages/marketing/sections/AnalyticsSection';
import { PricingSection } from '@/pages/marketing/sections/PricingSection';
import { FaqSection } from '@/pages/marketing/sections/FaqSection';
import { ClosingCtaSection } from '@/pages/marketing/sections/ClosingCtaSection';
import { LandingFooter } from '@/pages/marketing/sections/LandingFooter';

/**
 * Public marketing page shown at `/` to unauthenticated visitors (see
 * RequireAuth) — authenticated users get the dashboard at the same path
 * instead. Deliberately outside AppLayout: no sidebar/topbar chrome, just
 * this page's own header, sections, and footer.
 *
 * `space-y-*` (rather than padding on each section) gives the ~120px gap
 * DESIGN_SYSTEM.md calls for between sections without doubling it at each
 * boundary the way top+bottom padding on adjacent sections would.
 */
export function LandingPage() {
  return (
    <div className="min-h-svh bg-background">
      <LandingHeader />
      <main className="space-y-16 pb-16 sm:space-y-24 sm:pb-24 lg:space-y-[120px] lg:pb-[120px]">
        <HeroSection />
        <ModulesSection />
        <AutomationSection />
        <AiAssistantSection />
        <AnalyticsSection />
        <PricingSection />
        <FaqSection />
        <ClosingCtaSection />
      </main>
      <LandingFooter />
    </div>
  );
}
