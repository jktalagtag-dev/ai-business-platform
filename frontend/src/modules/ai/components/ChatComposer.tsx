import { useState, type KeyboardEvent } from 'react';
import { Loader2, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

const MAX_LENGTH = 8000; // matches SendAiMessageRequest's max:8000

export function ChatComposer({
  disabled,
  onSend,
}: {
  disabled?: boolean;
  onSend: (content: string) => void;
}) {
  const [value, setValue] = useState('');

  function handleSend() {
    const trimmed = value.trim();
    if (!trimmed || disabled) return;
    onSend(trimmed);
    setValue('');
  }

  function handleKeyDown(e: KeyboardEvent<HTMLTextAreaElement>) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  }

  return (
    <div className="flex items-end gap-2 border-t pt-3">
      <Textarea
        value={value}
        onChange={(e) => setValue(e.target.value.slice(0, MAX_LENGTH))}
        onKeyDown={handleKeyDown}
        placeholder="Ask the assistant… (Enter to send, Shift+Enter for a new line)"
        rows={2}
        maxLength={MAX_LENGTH}
        disabled={disabled}
        className="resize-none"
      />
      <Button type="button" onClick={handleSend} disabled={disabled || !value.trim()}>
        {disabled ? <Loader2 className="animate-spin" /> : <Send className="h-4 w-4" />}
        Send
      </Button>
    </div>
  );
}
