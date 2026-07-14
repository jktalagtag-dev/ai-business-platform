import { z } from 'zod';

/**
 * Zod schemas mirroring the backend Form Requests (client-side UX guardrail
 * only — the server re-validates and its `error.details` are mapped back onto
 * these same fields via applyApiErrorsToForm).
 */

const email = z.string().min(1, 'Email is required').email('Enter a valid email address');
const password = z.string().min(8, 'Password must be at least 8 characters');

export const loginSchema = z.object({
  email,
  password: z.string().min(1, 'Password is required'),
  tenant_slug: z.string().optional(),
});
export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z
  .object({
    name: z.string().min(1, 'Name is required'),
    email,
    tenant_name: z.string().min(1, 'Organization name is required'),
    password,
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });
export type RegisterFormValues = z.infer<typeof registerSchema>;

export const forgotPasswordSchema = z.object({ email });
export type ForgotPasswordFormValues = z.infer<typeof forgotPasswordSchema>;

export const resetPasswordSchema = z
  .object({
    email,
    token: z.string().min(1, 'Reset token is required'),
    password,
    password_confirmation: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
  });
export type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>;

export const updateProfileSchema = z.object({
  name: z.string().min(1, 'Name is required'),
  email,
});
export type UpdateProfileFormValues = z.infer<typeof updateProfileSchema>;
