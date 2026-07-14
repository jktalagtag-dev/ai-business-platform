import { useEffect, type ReactNode } from 'react';
import { useUiStore } from '@/store/uiStore';

function applyTheme(theme: 'light' | 'dark' | 'system'): () => void {
  const root = document.documentElement;
  const media = window.matchMedia('(prefers-color-scheme: dark)');

  const resolve = () => {
    const dark = theme === 'dark' || (theme === 'system' && media.matches);
    root.classList.toggle('dark', dark);
  };

  resolve();

  if (theme === 'system') {
    media.addEventListener('change', resolve);
    return () => media.removeEventListener('change', resolve);
  }
  return () => {};
}

/**
 * Applies the persisted theme preference to `<html class="dark">`, and — when
 * set to "system" — tracks OS preference changes live. Class-based dark mode
 * per FRONTEND.md §5.
 */
export function ThemeProvider({ children }: { children: ReactNode }) {
  const theme = useUiStore((s) => s.theme);

  useEffect(() => applyTheme(theme), [theme]);

  return <>{children}</>;
}
