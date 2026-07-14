import { config } from '@/lib/config';
import { apiErrorFromBody, ApiError } from '@/lib/errors';
import { clearAuthSession, getAuthToken } from '@/store/authStore';
import type { PaginationMeta, Page } from '@/types/api';

type QueryValue = string | number | boolean | undefined | null;

export interface RequestOptions {
  query?: Record<string, QueryValue>;
  /** JSON body — serialized to `application/json`. Ignored when `form` is set. */
  body?: unknown;
  /** Multipart body — sent as-is (browser sets the multipart boundary). */
  form?: FormData;
  signal?: AbortSignal;
}

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

function buildUrl(path: string, query?: Record<string, QueryValue>): string {
  const url = `${config.apiBaseUrl}${path}`;
  if (!query) return url;

  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== null) params.set(key, String(value));
  }
  const qs = params.toString();
  return qs ? `${url}?${qs}` : url;
}

/**
 * When an authenticated request comes back 401 the token is invalid/expired.
 * There is no refresh endpoint on this backend, so the only recovery is to
 * drop the session and send the user back to login.
 */
function handleUnauthorized(): void {
  clearAuthSession();
  if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
    try {
      window.location.assign('/login');
    } catch {
      /* jsdom / non-browser — ignore navigation */
    }
  }
}

async function rawRequest(
  method: HttpMethod,
  path: string,
  options: RequestOptions
): Promise<{ status: number; body: unknown }> {
  const token = getAuthToken();
  const headers: Record<string, string> = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;

  let payload: BodyInit | undefined;
  if (options.form) {
    payload = options.form;
  } else if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(options.body);
  }

  const response = await fetch(buildUrl(path, options.query), {
    method,
    headers,
    body: payload,
    signal: options.signal,
  });

  const text = await response.text();
  const body: unknown = text ? JSON.parse(text) : null;

  if (!response.ok) {
    if (response.status === 401 && token) handleUnauthorized();
    throw apiErrorFromBody(
      body as { error?: never; meta?: never } | null,
      response.status
    );
  }

  return { status: response.status, body };
}

function unwrapData<T>(body: unknown): T {
  const envelope = body as { data?: T } | null;
  if (!envelope || !('data' in envelope)) {
    throw new ApiError({
      code: 'malformed_response',
      message: 'The server response was missing a data envelope.',
      status: 500,
    });
  }
  return envelope.data as T;
}

/** Perform a request and return the unwrapped `data`. */
export async function apiRequest<T>(
  method: HttpMethod,
  path: string,
  options: RequestOptions = {}
): Promise<T> {
  const { body } = await rawRequest(method, path, options);
  return unwrapData<T>(body);
}

/** Perform a list request and return the items plus cursor pagination meta. */
export async function apiRequestPage<T>(
  method: HttpMethod,
  path: string,
  options: RequestOptions = {}
): Promise<Page<T>> {
  const { body } = await rawRequest(method, path, options);
  const envelope = body as {
    data?: T[];
    meta?: { pagination?: PaginationMeta };
  } | null;

  return {
    items: envelope?.data ?? [],
    pagination:
      envelope?.meta?.pagination ??
      ({ next_cursor: null, prev_cursor: null, per_page: 0 } satisfies PaginationMeta),
  };
}

export const api = {
  get: <T>(path: string, options?: RequestOptions) => apiRequest<T>('GET', path, options),
  getPage: <T>(path: string, options?: RequestOptions) => apiRequestPage<T>('GET', path, options),
  post: <T>(path: string, body?: unknown, options?: RequestOptions) =>
    apiRequest<T>('POST', path, { ...options, body }),
  postForm: <T>(path: string, form: FormData, options?: RequestOptions) =>
    apiRequest<T>('POST', path, { ...options, form }),
  put: <T>(path: string, body?: unknown, options?: RequestOptions) =>
    apiRequest<T>('PUT', path, { ...options, body }),
  patch: <T>(path: string, body?: unknown, options?: RequestOptions) =>
    apiRequest<T>('PATCH', path, { ...options, body }),
  delete: <T>(path: string, options?: RequestOptions) => apiRequest<T>('DELETE', path, options),
};
