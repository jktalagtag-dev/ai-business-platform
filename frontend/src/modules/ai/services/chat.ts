import { config } from '@/lib/config';
import { apiErrorFromBody } from '@/lib/errors';
import { getAuthToken } from '@/store/authStore';
import type { ChatStreamEvent, ChatStreamEventName } from '@/modules/ai/types';

/**
 * The send-message endpoint is a `text/event-stream` StreamedResponse, but
 * it's a POST carrying a JSON body and an `Authorization` header — the
 * browser's native `EventSource` API only supports GET with no custom
 * headers, so it can't be used here. Instead this hand-rolls SSE parsing
 * over a `fetch` + `ReadableStream`, buffering on blank-line-terminated
 * frames (`event: ...\ndata: ...\n\n`).
 *
 * A failure *before* the stream starts (invalid/forbidden conversation) is a
 * normal JSON `{error,meta}` response and throws an `ApiError` exactly like
 * every other endpoint. A failure *mid-stream* instead arrives as a bespoke
 * `event: error` frame with a bare `{ message }` — no `code`/`request_id` —
 * surfaced to the caller as a `ChatStreamEvent` rather than a thrown error,
 * since by then the response headers (200 OK) are already committed.
 */
export async function* streamChatMessage(
  conversationId: string,
  content: string,
  signal?: AbortSignal
): AsyncGenerator<ChatStreamEvent> {
  const token = getAuthToken();
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'text/event-stream',
  };
  if (token) headers.Authorization = `Bearer ${token}`;

  const response = await fetch(`${config.apiBaseUrl}/ai/conversations/${conversationId}/messages`, {
    method: 'POST',
    headers,
    body: JSON.stringify({ content }),
    signal,
  });

  if (!response.ok || !response.body) {
    const text = await response.text();
    const body = text ? JSON.parse(text) : null;
    throw apiErrorFromBody(body, response.status);
  }

  const reader = response.body.getReader();
  const decoder = new TextDecoder();
  let buffer = '';

  try {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });

      let separatorIndex: number;
      while ((separatorIndex = buffer.indexOf('\n\n')) !== -1) {
        const frame = buffer.slice(0, separatorIndex);
        buffer = buffer.slice(separatorIndex + 2);
        const event = parseSseFrame(frame);
        if (event) yield event;
      }
    }
  } finally {
    reader.releaseLock();
  }
}

function parseSseFrame(frame: string): ChatStreamEvent | null {
  let eventName: ChatStreamEventName = 'message';
  const dataLines: string[] = [];

  for (const line of frame.split('\n')) {
    if (line.startsWith('event:')) {
      eventName = line.slice('event:'.length).trim() as ChatStreamEventName;
    } else if (line.startsWith('data:')) {
      dataLines.push(line.slice('data:'.length).trim());
    }
  }

  if (dataLines.length === 0) return null;

  try {
    return { event: eventName, data: JSON.parse(dataLines.join('\n')) };
  } catch {
    return null;
  }
}
