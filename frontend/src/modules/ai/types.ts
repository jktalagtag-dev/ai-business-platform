import type { Resource } from '@/types/api';

export interface AiConversationAttributes {
  title: string | null;
  system_prompt: string | null;
  provider: string;
  model: string;
  total_prompt_tokens: number;
  total_completion_tokens: number;
  created_at: string;
  updated_at: string;
}

export type AiConversationResource = Resource<'ai_conversation', AiConversationAttributes>;

/** All optional — omitting everything creates an untitled conversation using
 * the backend's configured default model/system prompt. There is no rename
 * endpoint, so title can only ever be set here, at creation time. */
export interface CreateAiConversationPayload {
  title?: string | null;
  system_prompt?: string | null;
  model?: string | null;
}

export interface AiConversationListParams {
  per_page?: number;
  cursor?: string;
}

export type AiMessageRole = 'system' | 'user' | 'assistant' | 'tool';

export interface ToolCall {
  id: string;
  name: string;
  arguments: Record<string, unknown>;
}

export interface AiMessageAttributes {
  role: AiMessageRole;
  content: string;
  tool_calls: ToolCall[] | null;
  tool_call_id: string | null;
  name: string | null;
  prompt_tokens: number | null;
  completion_tokens: number | null;
  created_at: string;
}

export type AiMessageResource = Resource<'ai_message', AiMessageAttributes>;

/** No delete/edit/regenerate endpoint exists — messages are append-only. */
export interface SendAiMessagePayload {
  content: string;
}

// --- Streaming (SSE over a POST fetch — the backend's send-message endpoint
// is a `text/event-stream` response, but since it's a POST with an auth
// header, the browser's EventSource API can't be used; see services/chat.ts) ---

export type ChatStreamEventName = 'user_message' | 'delta' | 'tool_call' | 'tool_result' | 'message' | 'error';

export interface ChatStreamEvent {
  event: ChatStreamEventName;
  data: unknown;
}

export interface ChatDeltaData {
  content?: string;
}

export interface ChatToolCallData {
  id?: string;
  name?: string;
  arguments?: Record<string, unknown>;
}

export interface ChatToolResultData {
  id?: string;
  name?: string;
  result?: unknown;
}

/** Errors mid-stream arrive as a bespoke `{ message }` frame — no `code` or
 * `request_id`, unlike every other error path in this API. */
export interface ChatErrorData {
  message?: string;
}

/**
 * A single renderable chat item — shared shape for both persisted history
 * (mapped from AiMessageResource) and live in-flight SSE-derived entries, so
 * one rendering component handles both without a separate "live" variant.
 */
export interface ChatEntry {
  id: string;
  role: AiMessageRole;
  content: string;
  toolCalls: ToolCall[] | null;
  toolCallId: string | null;
  toolName: string | null;
  createdAt: string;
  /** True only for the assistant bubble currently receiving `delta` chunks. */
  streaming?: boolean;
}
