import { api } from '@/lib/api-client';
import type { KbDocumentListParams, KbDocumentResource } from '@/modules/kb/types';

/** No update endpoint exists — a document's title can only be set at upload
 * time and never changed afterward; a failed document can only be deleted
 * and re-uploaded, not re-processed in place. */
export const documentService = {
  list: (params: KbDocumentListParams = {}) =>
    api.getPage<KbDocumentResource>('/knowledge-base/documents', { query: { ...params } }),

  get: (id: string) => api.get<KbDocumentResource>(`/knowledge-base/documents/${id}`),

  upload: (file: File, title?: string) => {
    const form = new FormData();
    form.append('file', file);
    if (title) form.append('title', title);
    return api.postForm<KbDocumentResource>('/knowledge-base/documents', form);
  },

  remove: (id: string) => api.delete<void>(`/knowledge-base/documents/${id}`),
};
