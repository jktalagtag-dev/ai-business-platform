import { describe, it, expect, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { RequireAuth } from '@/routes/guards/RequireAuth';
import { RequireAbility } from '@/routes/guards/RequireAbility';
import { RequireRole } from '@/routes/guards/RequireRole';
import { useAuthStore } from '@/store/authStore';
import { makeAuthResource } from '@/tests/fixtures';

function renderAt(initial: string, ui: React.ReactNode) {
  return render(<MemoryRouter initialEntries={[initial]}>{ui}</MemoryRouter>);
}

beforeEach(() => useAuthStore.getState().clear());

describe('RequireAuth', () => {
  it('redirects to /login when unauthenticated', () => {
    renderAt(
      '/secret',
      <Routes>
        <Route element={<RequireAuth />}>
          <Route path="/secret" element={<div>secret content</div>} />
        </Route>
        <Route path="/login" element={<div>login page</div>} />
      </Routes>
    );
    expect(screen.getByText('login page')).toBeInTheDocument();
    expect(screen.queryByText('secret content')).not.toBeInTheDocument();
  });

  it('renders the protected route when authenticated', () => {
    useAuthStore.getState().setSession(makeAuthResource());
    renderAt(
      '/secret',
      <Routes>
        <Route element={<RequireAuth />}>
          <Route path="/secret" element={<div>secret content</div>} />
        </Route>
        <Route path="/login" element={<div>login page</div>} />
      </Routes>
    );
    expect(screen.getByText('secret content')).toBeInTheDocument();
  });
});

describe('RequireAbility', () => {
  it('redirects to /403 when the permission is missing', () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: ['tickets.view'] }));
    renderAt(
      '/inventory',
      <Routes>
        <Route element={<RequireAbility ability="products.view" />}>
          <Route path="/inventory" element={<div>inventory</div>} />
        </Route>
        <Route path="/403" element={<div>forbidden</div>} />
      </Routes>
    );
    expect(screen.getByText('forbidden')).toBeInTheDocument();
  });

  it('renders when the permission is present', () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: ['products.view'] }));
    renderAt(
      '/inventory',
      <Routes>
        <Route element={<RequireAbility ability="products.view" />}>
          <Route path="/inventory" element={<div>inventory</div>} />
        </Route>
        <Route path="/403" element={<div>forbidden</div>} />
      </Routes>
    );
    expect(screen.getByText('inventory')).toBeInTheDocument();
  });

  it('renders with `abilities` when any one of them is present', () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: ['inventory.view'] }));
    renderAt(
      '/inventory',
      <Routes>
        <Route
          element={
            <RequireAbility abilities={['products.view', 'categories.view', 'inventory.view']} />
          }
        >
          <Route path="/inventory" element={<div>inventory</div>} />
        </Route>
        <Route path="/403" element={<div>forbidden</div>} />
      </Routes>
    );
    expect(screen.getByText('inventory')).toBeInTheDocument();
  });

  it('redirects with `abilities` when none of them are present', () => {
    useAuthStore.getState().setSession(makeAuthResource({ permissions: ['tickets.view'] }));
    renderAt(
      '/inventory',
      <Routes>
        <Route
          element={
            <RequireAbility abilities={['products.view', 'categories.view', 'inventory.view']} />
          }
        >
          <Route path="/inventory" element={<div>inventory</div>} />
        </Route>
        <Route path="/403" element={<div>forbidden</div>} />
      </Routes>
    );
    expect(screen.getByText('forbidden')).toBeInTheDocument();
  });
});

describe('RequireRole', () => {
  it('redirects to /403 when the role is not in the allowed list', () => {
    useAuthStore.getState().setSession(makeAuthResource({ roleName: 'Member' }));
    renderAt(
      '/settings/audit-log',
      <Routes>
        <Route element={<RequireRole roles={['Owner', 'Admin']} />}>
          <Route path="/settings/audit-log" element={<div>audit log</div>} />
        </Route>
        <Route path="/403" element={<div>forbidden</div>} />
      </Routes>
    );
    expect(screen.getByText('forbidden')).toBeInTheDocument();
  });

  it('renders when the role is in the allowed list', () => {
    useAuthStore.getState().setSession(makeAuthResource({ roleName: 'Admin' }));
    renderAt(
      '/settings/audit-log',
      <Routes>
        <Route element={<RequireRole roles={['Owner', 'Admin']} />}>
          <Route path="/settings/audit-log" element={<div>audit log</div>} />
        </Route>
        <Route path="/403" element={<div>forbidden</div>} />
      </Routes>
    );
    expect(screen.getByText('audit log')).toBeInTheDocument();
  });
});
