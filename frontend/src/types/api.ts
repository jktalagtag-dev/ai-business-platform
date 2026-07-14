/**
 * Shared shapes for the backend's response envelope (see API.md §3).
 * Every endpoint returns `{ data, meta }`; lists add `meta.pagination`
 * (cursor-based — no page numbers or totals); errors return `{ error, meta }`.
 */

export interface Meta {
  request_id: string | null;
}

export interface PaginationMeta {
  next_cursor: string | null;
  prev_cursor: string | null;
  per_page: number;
}

export interface ApiEnvelope<T> {
  data: T;
  meta: Meta;
}

export interface PaginatedEnvelope<T> {
  data: T[];
  meta: Meta & { pagination: PaginationMeta };
}

/** A page of already-unwrapped items plus its cursor metadata. */
export interface Page<T> {
  items: T[];
  pagination: PaginationMeta;
}

/** JSON:API-ish resource object used by every backend Resource class. */
export interface Resource<TType extends string, TAttributes> {
  id: string;
  type: TType;
  attributes: TAttributes;
}

/** A single field-level validation error (a field may appear more than once). */
export interface ApiErrorDetail {
  field: string;
  message: string;
}

/** The `error` object inside a non-2xx response body. */
export interface ApiErrorBody {
  code: string;
  message: string;
  details?: ApiErrorDetail[];
  // Some errors merge extra context (e.g. `available_tenants` on a 409).
  [key: string]: unknown;
}
