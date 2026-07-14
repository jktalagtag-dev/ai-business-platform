import { describe, it, expect, vi } from 'vitest';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { ApiError } from '@/lib/errors';
import type { FieldValues, UseFormSetError } from 'react-hook-form';

describe('applyApiErrorsToForm', () => {
  it('maps each validation detail onto its field', () => {
    const setError = vi.fn() as unknown as UseFormSetError<FieldValues>;
    const error = new ApiError({
      code: 'validation_failed',
      message: 'invalid',
      status: 422,
      details: [
        { field: 'email', message: 'Required.' },
        { field: 'password', message: 'Too short.' },
      ],
    });

    const applied = applyApiErrorsToForm(error, setError);

    expect(applied).toBe(true);
    expect(setError).toHaveBeenCalledTimes(2);
    expect(setError).toHaveBeenCalledWith('email', { type: 'server', message: 'Required.' });
    expect(setError).toHaveBeenCalledWith('password', { type: 'server', message: 'Too short.' });
  });

  it('returns false for a non-validation error so the caller surfaces it globally', () => {
    const setError = vi.fn() as unknown as UseFormSetError<FieldValues>;
    const error = new ApiError({ code: 'forbidden', message: 'nope', status: 403 });

    expect(applyApiErrorsToForm(error, setError)).toBe(false);
    expect(setError).not.toHaveBeenCalled();
  });

  it('returns false for a non-ApiError', () => {
    const setError = vi.fn() as unknown as UseFormSetError<FieldValues>;
    expect(applyApiErrorsToForm(new Error('boom'), setError)).toBe(false);
  });
});
