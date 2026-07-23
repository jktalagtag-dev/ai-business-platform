import { Link } from 'react-router-dom';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { paths } from '@/routes/routes.config';
import { ProductPreviewMock } from '@/pages/marketing/sections/ProductPreviewMock';

export function HeroSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 pt-16 sm:px-6 sm:pt-24 lg:px-8">
      <div className="mx-auto max-w-3xl text-center">
        <h1 className="text-h1 font-bold tracking-tight md:text-display">
          The operating system for modern businesses
        </h1>
        <p className="mt-6 text-lg text-muted-foreground">
          Inventory, HR, support tickets, workflow automation, and an AI assistant that actually
          knows your data — in one platform instead of five.
        </p>
        <div className="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
          <Button size="lg" asChild>
            <Link to={paths.register}>
              Get started free
              <ArrowRight className="h-4 w-4" />
            </Link>
          </Button>
          <Button size="lg" variant="outline" asChild>
            <Link to={paths.login}>Log in</Link>
          </Button>
        </div>
      </div>

      <div className="mt-16 sm:mt-20">
        <ProductPreviewMock />
      </div>
    </section>
  );
}
