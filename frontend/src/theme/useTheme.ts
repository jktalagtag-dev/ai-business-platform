import { useUiStore, type Theme } from '@/store/uiStore';

export interface UseThemeResult {
  theme: Theme;
  setTheme: (theme: Theme) => void;
}

/** Read/update the persisted theme preference. Applying it to the DOM is the
 *  ThemeProvider's job. */
export function useTheme(): UseThemeResult {
  const theme = useUiStore((s) => s.theme);
  const setTheme = useUiStore((s) => s.setTheme);
  return { theme, setTheme };
}
