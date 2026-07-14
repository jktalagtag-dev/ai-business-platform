import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useChatStream } from '@/modules/ai/hooks/useChatStream';
import { streamChatMessage } from '@/modules/ai/services/chat';
import type { ChatStreamEvent } from '@/modules/ai/types';

vi.mock('@/modules/ai/services/chat', () => ({
  streamChatMessage: vi.fn(),
}));

function wrapper({ children }: { children: ReactNode }) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}

async function* fakeStream(events: ChatStreamEvent[]): AsyncGenerator<ChatStreamEvent> {
  for (const event of events) yield event;
}

beforeEach(() => vi.clearAllMocks());

describe('useChatStream', () => {
  it('optimistically adds the user message, then accumulates delta chunks into one assistant bubble', async () => {
    vi.mocked(streamChatMessage).mockReturnValue(
      fakeStream([
        { event: 'delta', data: { content: 'Hi' } },
        { event: 'delta', data: { content: ' there' } },
      ])
    );

    const { result } = renderHook(() => useChatStream('conv_1'), { wrapper });

    await act(async () => {
      await result.current.sendMessage('hello');
    });

    // The turn completed and the live overlay is cleared (history refetch supersedes it).
    expect(result.current.isStreaming).toBe(false);
    expect(result.current.liveEntries).toEqual([]);
  });

  it('surfaces tool_call then tool_result as separate entries mid-stream', async () => {
    let capturedDuringStream: unknown;
    vi.mocked(streamChatMessage).mockImplementation(() =>
      (async function* () {
        yield { event: 'tool_call', data: { id: 'call_1', name: 'search_knowledge_base', arguments: { query: 'Q3' } } } as ChatStreamEvent;
        capturedDuringStream = 'tool_call_yielded';
        yield { event: 'tool_result', data: { id: 'call_1', name: 'search_knowledge_base', result: { results: [] } } } as ChatStreamEvent;
      })()
    );

    const { result } = renderHook(() => useChatStream('conv_1'), { wrapper });

    await act(async () => {
      await result.current.sendMessage('search for Q3 docs');
    });

    expect(capturedDuringStream).toBe('tool_call_yielded');
    expect(result.current.liveEntries).toEqual([]); // cleared after completion
  });

  it('surfaces a mid-stream error event without throwing', async () => {
    vi.mocked(streamChatMessage).mockReturnValue(
      fakeStream([{ event: 'error', data: { message: 'The model timed out.' } }])
    );

    const { result } = renderHook(() => useChatStream('conv_1'), { wrapper });

    await act(async () => {
      await result.current.sendMessage('hello');
    });

    await waitFor(() => expect(result.current.streamError).toBe('The model timed out.'));
  });

  it('ignores an empty/whitespace-only message', async () => {
    const { result } = renderHook(() => useChatStream('conv_1'), { wrapper });

    await act(async () => {
      await result.current.sendMessage('   ');
    });

    expect(streamChatMessage).not.toHaveBeenCalled();
  });
});
