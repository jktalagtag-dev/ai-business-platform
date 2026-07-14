import type { AiMessageResource, ChatEntry } from '@/modules/ai/types';

export function messageResourceToChatEntry(message: AiMessageResource): ChatEntry {
  return {
    id: message.id,
    role: message.attributes.role,
    content: message.attributes.content,
    toolCalls: message.attributes.tool_calls,
    toolCallId: message.attributes.tool_call_id,
    toolName: message.attributes.name,
    createdAt: message.attributes.created_at,
  };
}
