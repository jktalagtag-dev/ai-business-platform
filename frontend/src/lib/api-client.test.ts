import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { api } from '@/lib/api-client';
import { isApiError } from '@/lib/errors';
import { useAuthStore } from '@/store/authStore';

function stubFetch(status: number, body: unknown) {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue({
      ok: status >= 200 && status < 300,
      status,
      text: async () => (body === null ? '' : JSON.stringify(body)),
    })
  );
}

beforeEach(() => {
  useAuthStore.getState().clear();
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

describe('api-client', () => {
  it('unwraps the data envelope on success', async () => {
    stubFetch(200, { data: { id: '1', name: 'Widget' }, meta: { request_id: 'req_1' } });
    const result = await api.get<{ id: string; name: string }>('/things/1');
    expect(result).toEqual({ id: '1', name: 'Widget' });
  });

  it('returns items + pagination for a list request', async () => {
    stubFetch(200, {
      data: [{ id: '1' }, { id: '2' }],
      meta: { request_id: 'req_2', pagination: { next_cursor: 'abc', prev_cursor: null, per_page: 25 } },
    });
    const page = await api.getPage<{ id: string }>('/things');
    expect(page.items).toHaveLength(2);
    expect(page.pagination.next_cursor).toBe('abc');
  });

  it('throws a typed ApiError with flattened validation details on 422', async () => {
    stubFetch(422, {
      error: {
        code: 'validation_failed',
        message: 'The given data was invalid.',
        details: [{ field: 'email', message: 'The email field is required.' }],
      },
      meta: { request_id: 'req_3' },
    });

    try {
      await api.post('/things', {});
      throw new Error('should have thrown');
    } catch (error) {
      expect(isApiError(error)).toBe(true);
      if (isApiError(error)) {
        expect(error.code).toBe('validation_failed');
        expect(error.status).toBe(422);
        expect(error.details[0]?.field).toBe('email');
      }
    }
  });

  it('exposes the 409 tenant-conflict context', async () => {
    stubFetch(409, {
      error: {
        code: 'conflict',
        message: 'Multiple tenants.',
        available_tenants: [{ id: 't1', name: 'Acme', slug: 'acme' }],
      },
      meta: { request_id: 'req_4' },
    });

    try {
      await api.post('/auth/login', {});
      throw new Error('should have thrown');
    } catch (error) {
      expect(isApiError(error) && error.isTenantAmbiguous()).toBe(true);
    }
  });

  it('clears the session on a 401 for an authenticated request', async () => {
    // Pretend we're already on /login so handleUnauthorized skips the hard
    // redirect (jsdom can't navigate) — the session-clear is what matters.
    window.history.pushState({}, '', '/login');
    useAuthStore.setState({ token: 'stale-token' });
    stubFetch(401, { error: { code: 'unauthenticated', message: 'Authentication required.' } });

    await expect(api.get('/profile')).rejects.toThrow();
    expect(useAuthStore.getState().token).toBeNull();
  });

  it('attaches the bearer token when a session exists', async () => {
    useAuthStore.setState({ token: 'live-token' });
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      text: async () => JSON.stringify({ data: {}, meta: { request_id: 'r' } }),
    });
    vi.stubGlobal('fetch', fetchMock);

    await api.get('/profile');

    const [, init] = fetchMock.mock.calls[0];
    expect((init.headers as Record<string, string>).Authorization).toBe('Bearer live-token');
  });
});
