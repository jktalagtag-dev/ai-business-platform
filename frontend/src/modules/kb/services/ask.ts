import { api } from '@/lib/api-client';
import type { AskPayload, AskResponse } from '@/modules/kb/types';

/** A plain request/response — not streaming, and nothing is persisted
 * server-side (unlike AI Assistant's conversations/messages). */
export const askService = {
  ask: (payload: AskPayload) => api.post<AskResponse>('/knowledge-base/ask', payload),
};
