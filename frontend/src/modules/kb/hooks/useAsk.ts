import { useMutation } from '@tanstack/react-query';
import { askService } from '@/modules/kb/services/ask';
import type { AskPayload, AskResponse } from '@/modules/kb/types';

/** A mutation, not a query — each ask is a one-off request with no server-side
 * persistence; the caller is responsible for keeping any chat-like history
 * client-side (see AskPanel). */
export function useAsk() {
  return useMutation<AskResponse, unknown, AskPayload>({
    mutationFn: (payload) => askService.ask(payload),
  });
}
