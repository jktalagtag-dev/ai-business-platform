import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { paths } from '@/routes/routes.config';

export function ClosingCtaSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="rounded-dialog border bg-primary px-6 py-16 text-center text-primary-foreground sm:py-20">
        <h2 className="text-h2 font-bold tracking-tight">Ready to bring it all together?</h2>
        <p className="mx-auto mt-4 max-w-xl text-primary-foreground/90">
          Start free, invite your team, and see everything in one place.
        </p>
        <Button size="lg" variant="secondary" className="mt-8" asChild>
          <Link to={paths.register}>
            Get started free
            <ArrowRight className="h-4 w-4" />
          </Link>
        </Button>
      </div>
    </section>
  );
}
