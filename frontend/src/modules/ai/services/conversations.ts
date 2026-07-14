import { api } from '@/lib/api-client';
import type {
  AiConversationListParams,
  AiConversationResource,
  AiMessageResource,
  CreateAiConversationPayload,
} from '@/modules/ai/types';

/** Conversations are strictly per-user (not tenant-wide) — the backend scopes
 * list/find to the caller's own rows; there is no "view all" ability, so
 * even Owner/Admin only ever see their own conversations. No rename or
 * update endpoint exists. */
export const conversationService = {
  list: (params: AiConversationListParams = {}) =>
    api.getPage<AiConversationResource>('/ai/conversations', { query: { ...params } }),

  get: (id: string) => api.get<AiConversationResource>(`/ai/conversations/${id}`),

  create: (payload: CreateAiConversationPayload) =>
    api.post<AiConversationResource>('/ai/conversations', payload),

  remove: (id: string) => api.delete<void>(`/ai/conversations/${id}`),

  messages: (id: string, params: { per_page?: number; cursor?: string } = {}) =>
    api.getPage<AiMessageResource>(`/ai/conversations/${id}/messages`, { query: { ...params } }),
};
