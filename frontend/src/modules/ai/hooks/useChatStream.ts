import { useCallback, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { streamChatMessage } from '@/modules/ai/services/chat';
import { isApiError } from '@/lib/errors';
import type {
  ChatDeltaData,
  ChatEntry,
  ChatErrorData,
  ChatStreamEvent,
  ChatToolCallData,
  ChatToolResultData,
} from '@/modules/ai/types';

function newId(prefix: string): string {
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

/**
 * Drives one send/receive turn against the streaming chat endpoint. Persisted
 * history (via useConversationMessages) is the source of truth; this hook
 * only holds the *live* entries for the turn currently in flight, appended
 * after that history in the UI. Once the turn completes, the history query
 * is invalidated and awaited before clearing the live overlay, so the
 * transcript never flashes empty between "live" and "persisted" states.
 */
export function useChatStream(conversationId: string) {
  const queryClient = useQueryClient();
  const [liveEntries, setLiveEntries] = useState<ChatEntry[]>([]);
  const [isStreaming, setIsStreaming] = useState(false);
  const [streamError, setStreamError] = useState<string | null>(null);
  const streamingEntryId = useRef<string | null>(null);

  const handleEvent = useCallback((event: ChatStreamEvent) => {
    switch (event.event) {
      case 'delta': {
        const delta = (event.data as ChatDeltaData)?.content ?? '';
        setLiveEntries((prev) => {
          if (streamingEntryId.current) {
            return prev.map((entry) =>
              entry.id === streamingEntryId.current
                ? { ...entry, content: entry.content + delta }
                : entry
            );
          }
          const id = newId('stream');
          streamingEntryId.current = id;
          return [
            ...prev,
            {
              id,
              role: 'assistant',
              content: delta,
              toolCalls: null,
              toolCallId: null,
              toolName: null,
              createdAt: new Date().toISOString(),
              streaming: true,
            },
          ];
        });
        break;
      }
      case 'tool_call': {
        const data = event.data as ChatToolCallData;
        streamingEntryId.current = null; // the next delta (if any) starts a fresh bubble
        setLiveEntries((prev) => [
          ...prev,
          {
            id: newId('tool-call'),
            role: 'assistant',
            content: '',
            toolCalls: [{ id: data.id ?? '', name: data.name ?? 'unknown', arguments: data.arguments ?? {} }],
            toolCallId: null,
            toolName: null,
            createdAt: new Date().toISOString(),
          },
        ]);
        break;
      }
      case 'tool_result': {
        const data = event.data as ChatToolResultData;
        setLiveEntries((prev) => [
          ...prev,
          {
            id: newId('tool-result'),
            role: 'tool',
            content: JSON.stringify(data.result ?? {}),
            toolCalls: null,
            toolCallId: data.id ?? null,
            toolName: data.name ?? null,
            createdAt: new Date().toISOString(),
          },
        ]);
        break;
      }
      case 'message': {
        // The authoritative final assistant message — the invalidated
        // history refetch on completion supersedes this, so nothing to do.
        streamingEntryId.current = null;
        break;
      }
      case 'error': {
        const data = event.data as ChatErrorData;
        setStreamError(data?.message ?? 'The assistant encountered an error.');
        break;
      }
      case 'user_message':
      default:
        break;
    }
  }, []);

  const sendMessage = useCallback(
    async (content: string) => {
      const trimmed = content.trim();
      if (!trimmed || isStreaming) return;

      streamingEntryId.current = null;
      setStreamError(null);
      setLiveEntries([
        {
          id: newId('user'),
          role: 'user',
          content: trimmed,
          toolCalls: null,
          toolCallId: null,
          toolName: null,
          createdAt: new Date().toISOString(),
        },
      ]);
      setIsStreaming(true);

      try {
        for await (const event of streamChatMessage(conversationId, trimmed)) {
          handleEvent(event);
        }
      } catch (error) {
        setStreamError(
          isApiError(error) ? error.message : 'Connection to the assistant was lost.'
        );
      } finally {
        setIsStreaming(false);
        await queryClient.invalidateQueries({ queryKey: ['ai', 'messages', conversationId] });
        queryClient.invalidateQueries({ queryKey: ['ai', 'conversation', conversationId] });
        queryClient.invalidateQueries({ queryKey: ['ai', 'conversations'] });
        setLiveEntries([]);
        streamingEntryId.current = null;
      }
    },
    [conversationId, handleEvent, isStreaming, queryClient]
  );

  return { liveEntries, isStreaming, streamError, sendMessage };
}
