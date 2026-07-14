import { Toaster as SonnerToaster } from 'sonner';
import { useUiStore } from '@/store/uiStore';

/** App-wide toast portal. Mirrors the persisted theme preference. */
export function Toaster() {
  const theme = useUiStore((s) => s.theme);
  return (
    <SonnerToaster
      theme={theme}
      className="toaster group"
      toastOptions={{
        classNames: {
          toast:
            'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg',
          description: 'group-[.toast]:text-muted-foreground',
          actionButton: 'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
          cancelButton: 'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
        },
      }}
    />
  );
}

export { toast } from 'sonner';
