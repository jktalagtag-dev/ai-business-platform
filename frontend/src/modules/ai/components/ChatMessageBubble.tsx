import { cn } from '@/lib/cn';
import { ToolCallBlock } from '@/modules/ai/components/ToolCallBlock';
import type { ChatEntry } from '@/modules/ai/types';

export function ChatMessageBubble({ entry }: { entry: ChatEntry }) {
  if (entry.role === 'system') return null;

  if (entry.toolCalls && entry.toolCalls.length > 0) {
    return (
      <div className="flex flex-col gap-2">
        {entry.toolCalls.map((call) => (
          <ToolCallBlock key={call.id || call.name} kind="call" name={call.name} args={call.arguments} />
        ))}
      </div>
    );
  }

  if (entry.role === 'tool') {
    return <ToolCallBlock kind="result" name={entry.toolName ?? 'tool'} content={entry.content} />;
  }

  const isUser = entry.role === 'user';

  return (
    <div className={cn('flex', isUser ? 'justify-end' : 'justify-start')}>
      <div
        className={cn(
          'max-w-lg whitespace-pre-wrap rounded-lg px-3 py-2 text-sm',
          isUser ? 'bg-primary text-primary-foreground' : 'bg-muted'
        )}
      >
        {entry.content}
        {entry.streaming && <span className="ml-0.5 inline-block h-4 w-1.5 animate-pulse bg-current align-middle" />}
      </div>
    </div>
  );
}
