import { useMutation, useQueryClient } from '@tanstack/react-query';
import { authService } from '@/modules/auth/services/auth';
import { useAuthStore } from '@/store/authStore';

/**
 * Revoke the current token server-side, then clear local session + cache.
 * The local clear runs even if the network call fails — the user's intent to
 * end the session takes precedence over a best-effort server revoke.
 */
export function useLogout() {
  const clear = useAuthStore((s) => s.clear);
  const queryClient = useQueryClient();

  return useMutation<void, unknown, void>({
    mutationFn: async () => {
      try {
        await authService.logout();
      } catch {
        /* ignore — clear locally regardless */
      }
    },
    onSettled: () => {
      clear();
      queryClient.clear();
    },
  });
}
