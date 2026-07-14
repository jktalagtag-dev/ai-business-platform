import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { LoginForm } from '@/modules/auth/components/LoginForm';
import { authService } from '@/modules/auth/services/auth';
import { ApiError } from '@/lib/errors';

vi.mock('@/modules/auth/services/auth', () => ({
  authService: { login: vi.fn() },
}));

function renderLogin() {
  const queryClient = new QueryClient({ defaultOptions: { mutations: { retry: false } } });
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter>
        <LoginForm />
      </MemoryRouter>
    </QueryClientProvider>
  );
}

beforeEach(() => vi.clearAllMocks());

describe('LoginForm', () => {
  it('reveals an organization picker when login returns a tenant conflict', async () => {
    vi.mocked(authService.login).mockRejectedValue(
      new ApiError({
        code: 'conflict',
        message: 'Multiple organizations',
        status: 409,
        context: { available_tenants: [{ id: '1', name: 'Acme', slug: 'acme' }] },
      })
    );

    const user = userEvent.setup();
    renderLogin();

    await user.type(screen.getByLabelText('Email'), 'ada@example.com');
    await user.type(screen.getByLabelText('Password'), 'password123');
    await user.click(screen.getByRole('button', { name: /sign in/i }));

    expect(await screen.findByText('Organization')).toBeInTheDocument();
  });

  it('maps server validation errors onto the fields', async () => {
    vi.mocked(authService.login).mockRejectedValue(
      new ApiError({
        code: 'validation_failed',
        message: 'invalid',
        status: 422,
        details: [{ field: 'email', message: 'These credentials do not match our records.' }],
      })
    );

    const user = userEvent.setup();
    renderLogin();

    await user.type(screen.getByLabelText('Email'), 'ada@example.com');
    await user.type(screen.getByLabelText('Password'), 'password123');
    await user.click(screen.getByRole('button', { name: /sign in/i }));

    expect(
      await screen.findByText('These credentials do not match our records.')
    ).toBeInTheDocument();
  });

  it('blocks submission with a client-side validation message for a bad email', async () => {
    const user = userEvent.setup();
    renderLogin();

    await user.type(screen.getByLabelText('Email'), 'not-an-email');
    await user.type(screen.getByLabelText('Password'), 'password123');
    await user.click(screen.getByRole('button', { name: /sign in/i }));

    expect(await screen.findByText('Enter a valid email address')).toBeInTheDocument();
    expect(authService.login).not.toHaveBeenCalled();
  });
});
