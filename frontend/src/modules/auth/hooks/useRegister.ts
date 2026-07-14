import { useMutation } from '@tanstack/react-query';
import { authService } from '@/modules/auth/services/auth';
import { useAuthStore } from '@/store/authStore';
import type { AuthResource, RegisterPayload } from '@/modules/auth/types';

/** Register a new tenant + owner and store the returned session. */
export function useRegister() {
  const setSession = useAuthStore((s) => s.setSession);
  return useMutation<AuthResource, unknown, RegisterPayload>({
    mutationFn: (payload) => authService.register(payload),
    onSuccess: (auth) => setSession(auth),
  });
}
