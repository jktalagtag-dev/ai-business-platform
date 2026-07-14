import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authService } from '@/modules/auth/services/auth';
import { useAuthStore } from '@/store/authStore';
import type { ProfileResource, UpdateProfilePayload, UserResource } from '@/modules/auth/types';

export const profileQueryKey = ['profile'] as const;

/** The authenticated user's profile (user + all tenant memberships). */
export function useProfile() {
  return useQuery<ProfileResource>({
    queryKey: profileQueryKey,
    queryFn: () => authService.getProfile(),
  });
}

/** Update the user's name/email; syncs the local session's user on success. */
export function useUpdateProfile() {
  const setUser = useAuthStore((s) => s.setUser);
  const queryClient = useQueryClient();

  return useMutation<UserResource, unknown, UpdateProfilePayload>({
    mutationFn: (payload) => authService.updateProfile(payload),
    onSuccess: (user) => {
      setUser(user);
      queryClient.invalidateQueries({ queryKey: profileQueryKey });
    },
  });
}
