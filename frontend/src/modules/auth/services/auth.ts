import { api } from '@/lib/api-client';
import type {
  AuthResource,
  ForgotPasswordPayload,
  LoginPayload,
  ProfileResource,
  RegisterPayload,
  ResetPasswordPayload,
  UpdateProfilePayload,
  UserResource,
} from '@/modules/auth/types';

/**
 * Thin typed wrappers over the api-client — the only place auth endpoint URLs
 * live (frontend equivalent of the backend Repository layer). No caching or
 * retry logic here; that belongs to the Query hooks.
 */
export const authService = {
  register: (payload: RegisterPayload) => api.post<AuthResource>('/auth/register', payload),

  login: (payload: LoginPayload) => api.post<AuthResource>('/auth/login', payload),

  logout: () => api.post<{ message: string }>('/auth/logout'),

  forgotPassword: (payload: ForgotPasswordPayload) =>
    api.post<{ message: string }>('/auth/forgot-password', payload),

  resetPassword: (payload: ResetPasswordPayload) =>
    api.post<{ message: string }>('/auth/reset-password', payload),

  getProfile: () => api.get<ProfileResource>('/profile'),

  updateProfile: (payload: UpdateProfilePayload) => api.patch<UserResource>('/profile', payload),
};
