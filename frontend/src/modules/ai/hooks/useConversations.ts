import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { conversationService } from '@/modules/ai/services/conversations';
import type {
  AiConversationListParams,
  AiConversationResource,
  AiMessageResource,
  CreateAiConversationPayload,
} from '@/modules/ai/types';
import type { Page } from '@/types/api';

export function useConversations(filter: AiConversationListParams = {}) {
  return useQuery<Page<AiConversationResource>>({
    queryKey: ['ai', 'conversations', filter],
    queryFn: () => conversationService.list(filter),
  });
}

export function useConversation(id: string | undefined) {
  return useQuery<AiConversationResource>({
    queryKey: ['ai', 'conversation', id],
    queryFn: () => conversationService.get(id as string),
    enabled: !!id,
  });
}

export function useCreateConversation() {
  const queryClient = useQueryClient();
  return useMutation<AiConversationResource, unknown, CreateAiConversationPayload>({
    mutationFn: (payload) => conversationService.create(payload),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['ai', 'conversations'] }),
  });
}

export function useDeleteConversation() {
  const queryClient = useQueryClient();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => conversationService.remove(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['ai', 'conversations'] }),
  });
}

export function useConversationMessages(conversationId: string | undefined, cursor?: string) {
  return useQuery<Page<AiMessageResource>>({
    queryKey: ['ai', 'messages', conversationId, cursor],
    queryFn: () => conversationService.messages(conversationId as string, { cursor, per_page: 200 }),
    enabled: !!conversationId,
  });
}
