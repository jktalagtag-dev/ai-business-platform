import type { ApiErrorBody, ApiErrorDetail } from '@/types/api';

/**
 * Typed representation of a non-2xx API response, matching the backend's
 * error envelope (API.md §3.3). Thrown by the api-client so every hook and
 * component can branch on `code`/`status`/`details` instead of poking at
 * raw JSON.
 */
export class ApiError extends Error {
  readonly code: string;
  readonly status: number;
  readonly details: ApiErrorDetail[];
  /** Extra fields merged into the `error` object (e.g. `available_tenants`). */
  readonly context: Record<string, unknown>;
  readonly requestId: string | null;

  constructor(params: {
    code: string;
    message: string;
    status: number;
    details?: ApiErrorDetail[];
    context?: Record<string, unknown>;
    requestId?: string | null;
  }) {
    super(params.message);
    this.name = 'ApiError';
    this.code = params.code;
    this.status = params.status;
    this.details = params.details ?? [];
    this.context = params.context ?? {};
    this.requestId = params.requestId ?? null;
  }

  isValidation(): boolean {
    return this.code === 'validation_failed';
  }

  isUnauthenticated(): boolean {
    return this.status === 401;
  }

  isForbidden(): boolean {
    return this.status === 403;
  }

  /** Multi-tenant login ambiguity (409) — `context.available_tenants` is set. */
  isTenantAmbiguous(): boolean {
    return this.code === 'conflict' && Array.isArray(this.context.available_tenants);
  }
}

export function isApiError(error: unknown): error is ApiError {
  return error instanceof ApiError;
}

/** Build an ApiError from a parsed error-envelope body. */
export function apiErrorFromBody(
  body: { error?: ApiErrorBody; meta?: { request_id?: string | null } } | null,
  status: number
): ApiError {
  const error = body?.error;

  if (!error) {
    return new ApiError({
      code: 'unknown',
      message: 'An unexpected error occurred.',
      status,
      requestId: body?.meta?.request_id ?? null,
    });
  }

  const { code, message, details, ...context } = error;

  return new ApiError({
    code,
    message,
    status,
    details,
    context,
    requestId: body?.meta?.request_id ?? null,
  });
}
