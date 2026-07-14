import type { Resource } from '@/types/api';

/** `processing` (job not yet run/still running) → `ready` or `failed`. No
 * re-process endpoint exists — a failed document must be deleted and
 * re-uploaded. */
export type KbDocumentStatus = 'processing' | 'ready' | 'failed';

export interface KbDocumentAttributes {
  title: string;
  original_filename: string;
  mime_type: string;
  size_bytes: number;
  status: KbDocumentStatus;
  error_message: string | null;
  page_count: number | null;
  created_at: string;
  updated_at: string;
}

export type KbDocumentResource = Resource<'kb_document', KbDocumentAttributes>;

export interface KbDocumentListParams {
  per_page?: number;
  cursor?: string;
}

export interface UploadKbDocumentPayload {
  title?: string;
  file: File;
}

export interface Citation {
  number: number;
  document_id: string;
  title: string;
  chunk_index: number;
  page_number: number;
  /** Truncated (~240 char) excerpt of the source chunk — the backend never
   * exposes full chunk content or the embedding vector via any endpoint. */
  snippet: string;
  score: number;
}

export interface AskPayload {
  query: string;
  top_k?: number;
}

/** A single request/response — `/ask` is not streaming and nothing is
 * persisted server-side, unlike AI Assistant's conversations. */
export interface AskResponse {
  answer: string;
  citations: Citation[];
  usage: {
    prompt_tokens: number | null;
    completion_tokens: number | null;
  };
}
