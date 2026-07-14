import { describe, it, expect } from 'vitest';
import { messageResourceToChatEntry } from '@/modules/ai/mapMessage';
import { makeAiMessageResource } from '@/tests/fixtures';

describe('messageResourceToChatEntry', () => {
  it('maps a plain user message', () => {
    const entry = messageResourceToChatEntry(makeAiMessageResource({ role: 'user', content: 'Hi' }));
    expect(entry).toEqual({
      id: 'message_1',
      role: 'user',
      content: 'Hi',
      toolCalls: null,
      toolCallId: null,
      toolName: null,
      createdAt: '2026-07-13T10:00:00+00:00',
    });
  });

  it('maps an assistant tool-call message', () => {
    const toolCalls = [{ id: 'call_1', name: 'search_knowledge_base', arguments: { query: 'Q3' } }];
    const entry = messageResourceToChatEntry(
      makeAiMessageResource({ role: 'assistant', content: '', tool_calls: toolCalls })
    );
    expect(entry.toolCalls).toEqual(toolCalls);
  });

  it('maps a tool-result message', () => {
    const entry = messageResourceToChatEntry(
      makeAiMessageResource({
        role: 'tool',
        content: '{"results":[]}',
        tool_call_id: 'call_1',
        name: 'search_knowledge_base',
      })
    );
    expect(entry.role).toBe('tool');
    expect(entry.toolCallId).toBe('call_1');
    expect(entry.toolName).toBe('search_knowledge_base');
  });
});
