/** Env-derived runtime config. Vite inlines `import.meta.env.VITE_*` at build. */
export const config = {
  /** Base path for API calls; proxied to the backend by Vite in dev. */
  apiBaseUrl: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
} as const;
