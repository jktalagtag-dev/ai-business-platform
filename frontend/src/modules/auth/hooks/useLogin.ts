import { useMutation } from '@tanstack/react-query';
import { authService } from '@/modules/auth/services/auth';
import { useAuthStore } from '@/store/authStore';
import type { AuthResource, LoginPayload } from '@/modules/auth/types';

/** Log in and, on success, store the returned session. */
export function useLogin() {
  const setSession = useAuthStore((s) => s.setSession);
  return useMutation<AuthResource, unknown, LoginPayload>({
    mutationFn: (payload) => authService.login(payload),
    onSuccess: (auth) => setSession(auth),
  });
}
