import { describe, it, expect, vi, afterEach } from 'vitest';
import { streamChatMessage } from '@/modules/ai/services/chat';
import { isApiError } from '@/lib/errors';

function makeStreamResponse(chunks: string[]): Response {
  const encoder = new TextEncoder();
  const stream = new ReadableStream<Uint8Array>({
    start(controller) {
      for (const chunk of chunks) controller.enqueue(encoder.encode(chunk));
      controller.close();
    },
  });
  return { ok: true, status: 200, body: stream, text: async () => '' } as unknown as Response;
}

function makeErrorResponse(status: number, body: unknown): Response {
  return {
    ok: false,
    status,
    body: null,
    text: async () => JSON.stringify(body),
  } as unknown as Response;
}

afterEach(() => {
  vi.unstubAllGlobals();
});

async function collect<T>(gen: AsyncGenerator<T>): Promise<T[]> {
  const out: T[] = [];
  for await (const item of gen) out.push(item);
  return out;
}

describe('streamChatMessage', () => {
  it('parses a single complete SSE frame', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(makeStreamResponse(['event: delta\ndata: {"content":"Hi"}\n\n']))
    );

    const events = await collect(streamChatMessage('conv_1', 'hello'));
    expect(events).toEqual([{ event: 'delta', data: { content: 'Hi' } }]);
  });

  it('parses multiple frames split arbitrarily across chunk boundaries', async () => {
    const full =
      'event: tool_call\ndata: {"id":"call_1","name":"search_knowledge_base","arguments":{"query":"Q3"}}\n\n' +
      'event: tool_result\ndata: {"id":"call_1","name":"search_knowledge_base","result":{"results":[]}}\n\n' +
      'event: message\ndata: {"role":"assistant","content":"done"}\n\n';
    // Split mid-frame to prove the buffer correctly accumulates partial chunks.
    const chunks = [full.slice(0, 40), full.slice(40, 90), full.slice(90)];
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(makeStreamResponse(chunks)));

    const events = await collect(streamChatMessage('conv_1', 'hello'));
    expect(events.map((e) => e.event)).toEqual(['tool_call', 'tool_result', 'message']);
    expect(events[0].data).toEqual({
      id: 'call_1',
      name: 'search_knowledge_base',
      arguments: { query: 'Q3' },
    });
  });

  it('defaults to the "message" event name when no event line is present', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(makeStreamResponse(['data: {"content":"hi"}\n\n'])));

    const events = await collect(streamChatMessage('conv_1', 'hello'));
    expect(events).toEqual([{ event: 'message', data: { content: 'hi' } }]);
  });

  it('throws a typed ApiError for a pre-stream failure (e.g. an unauthorized conversation)', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn().mockResolvedValue(
        makeErrorResponse(403, { error: { code: 'forbidden', message: 'Not your conversation.' } })
      )
    );

    try {
      await collect(streamChatMessage('conv_1', 'hello'));
      throw new Error('should have thrown');
    } catch (error) {
      expect(isApiError(error)).toBe(true);
      if (isApiError(error)) {
        expect(error.code).toBe('forbidden');
        expect(error.status).toBe(403);
      }
    }
  });
});
