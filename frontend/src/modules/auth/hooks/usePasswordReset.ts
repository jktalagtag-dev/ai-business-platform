import { useMutation } from '@tanstack/react-query';
import { authService } from '@/modules/auth/services/auth';
import type { ForgotPasswordPayload, ResetPasswordPayload } from '@/modules/auth/types';

/** Request a password-reset link (always returns a generic success message). */
export function useForgotPassword() {
  return useMutation<{ message: string }, unknown, ForgotPasswordPayload>({
    mutationFn: (payload) => authService.forgotPassword(payload),
  });
}

/** Complete a password reset with the emailed token. */
export function useResetPassword() {
  return useMutation<{ message: string }, unknown, ResetPasswordPayload>({
    mutationFn: (payload) => authService.resetPassword(payload),
  });
}
