import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';
import { isApiError } from '@/lib/errors';

/**
 * Map a backend validation error onto React Hook Form fields. The backend
 * returns `error.details` as a flat list of `{ field, message }` (a field
 * may appear more than once); each is applied via RHF's `setError` so the
 * message renders inline on the matching input. Non-validation errors are
 * left for the caller to surface (e.g. a toast) and reported via the return.
 *
 * @returns true if the error was a field-level validation error that was
 *   applied to the form; false otherwise (caller should show it globally).
 */
export function applyApiErrorsToForm<TFieldValues extends FieldValues>(
  error: unknown,
  setError: UseFormSetError<TFieldValues>
): boolean {
  if (!isApiError(error) || !error.isValidation() || error.details.length === 0) {
    return false;
  }

  for (const detail of error.details) {
    setError(detail.field as Path<TFieldValues>, {
      type: 'server',
      message: detail.message,
    });
  }

  return true;
}
