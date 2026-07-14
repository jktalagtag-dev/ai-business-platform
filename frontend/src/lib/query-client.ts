import { QueryClient } from '@tanstack/react-query';
import { isApiError } from '@/lib/errors';

/**
 * Shared QueryClient. 4xx responses are deterministic client/authorization
 * errors, so they are never retried; transient/network and 5xx get one retry.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      refetchOnWindowFocus: false,
      retry: (failureCount, error) => {
        if (isApiError(error) && error.status >= 400 && error.status < 500) {
          return false;
        }
        return failureCount < 1;
      },
    },
    mutations: {
      retry: false,
    },
  },
});
