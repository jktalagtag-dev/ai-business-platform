import { describe, it, expect, beforeEach } from 'vitest';
import { useAuthStore, getAuthToken } from '@/store/authStore';
import { makeAuthResource } from '@/tests/fixtures';

beforeEach(() => useAuthStore.getState().clear());

describe('authStore', () => {
  it('maps a login response into session state', () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: ['tickets.view'] }));

    const state = useAuthStore.getState();
    expect(state.token).toBe('test-token');
    expect(state.user?.attributes.email).toBe('ada@example.com');
    expect(state.tenant?.slug).toBe('analytical-engines');
    expect(state.role?.name).toBe('Owner');
    expect(state.permissions).toEqual(['tickets.view']);
    expect(getAuthToken()).toBe('test-token');
  });

  it('clears everything on logout', () => {
    useAuthStore.getState().setSession(makeAuthResource());
    useAuthStore.getState().clear();

    const state = useAuthStore.getState();
    expect(state.token).toBeNull();
    expect(state.user).toBeNull();
    expect(state.permissions).toEqual([]);
  });

  it('updates only the user without dropping the token', () => {
    useAuthStore.getState().setSession(makeAuthResource());
    useAuthStore.getState().setUser({
      id: 'user_1',
      type: 'user',
      attributes: { name: 'Grace Hopper', email: 'grace@example.com', email_verified_at: null },
    });

    const state = useAuthStore.getState();
    expect(state.user?.attributes.name).toBe('Grace Hopper');
    expect(state.token).toBe('test-token');
  });
});
