import { useState, type KeyboardEvent } from 'react';
import { Loader2, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/layout/EmptyState';
import { toast } from '@/components/ui/sonner';
import { cn } from '@/lib/cn';
import { isApiError } from '@/lib/errors';
import { useAsk } from '@/modules/kb/hooks/useAsk';
import { CitationList } from '@/modules/kb/components/CitationList';
import type { AskResponse } from '@/modules/kb/types';

const MAX_LENGTH = 2000; // matches AskKnowledgeBaseRequest's max:2000

interface HistoryEntry {
  id: string;
  question: string;
  response?: AskResponse;
}

/** Each ask is a single stateless request/response — nothing is saved
 * server-side, so this history is purely client-side and clears on refresh
 * (unlike AI Assistant's persisted conversations). */
export function AskPanel() {
  const ask = useAsk();
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [value, setValue] = useState('');

  function handleAsk() {
    const query = value.trim();
    if (!query || ask.isPending) return;

    const entryId = `ask-${Date.now()}`;
    setHistory((prev) => [...prev, { id: entryId, question: query }]);
    setValue('');

    ask.mutate(
      { query },
      {
        onSuccess: (response) => {
          setHistory((prev) => prev.map((e) => (e.id === entryId ? { ...e, response } : e)));
        },
        onError: (error) => {
          setHistory((prev) => prev.filter((e) => e.id !== entryId));
          toast.error(isApiError(error) ? error.message : 'Unable to get an answer.');
        },
      }
    );
  }

  function handleKeyDown(e: KeyboardEvent<HTMLTextAreaElement>) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleAsk();
    }
  }

  return (
    <div className="flex flex-col gap-3">
      {history.length === 0 ? (
        <EmptyState
          title="Ask a question"
          description="Answers are drawn from your uploaded documents, with citations back to the source page."
        />
      ) : (
        <div className="space-y-4">
          {history.map((entry) => (
            <div key={entry.id} className="space-y-2">
              <div className="flex justify-end">
                <div className="max-w-lg rounded-lg bg-primary px-3 py-2 text-sm text-primary-foreground">
                  {entry.question}
                </div>
              </div>
              <div className="flex justify-start">
                <div
                  className={cn(
                    'max-w-lg whitespace-pre-wrap rounded-lg bg-muted px-3 py-2 text-sm',
                    !entry.response && 'text-muted-foreground'
                  )}
                >
                  {entry.response ? (
                    <>
                      {entry.response.answer}
                      <CitationList citations={entry.response.citations} />
                    </>
                  ) : (
                    <span className="inline-flex items-center gap-2">
                      <Loader2 className="h-3.5 w-3.5 animate-spin" />
                      Thinking…
                    </span>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      <div className="flex items-end gap-2 border-t pt-3">
        <Textarea
          value={value}
          onChange={(e) => setValue(e.target.value.slice(0, MAX_LENGTH))}
          onKeyDown={handleKeyDown}
          placeholder="Ask about your uploaded documents… (Enter to send, Shift+Enter for a new line)"
          rows={2}
          maxLength={MAX_LENGTH}
          disabled={ask.isPending}
          className="resize-none"
        />
        <Button type="button" onClick={handleAsk} disabled={ask.isPending || !value.trim()}>
          {ask.isPending ? <Loader2 className="animate-spin" /> : <Send className="h-4 w-4" />}
          Ask
        </Button>
      </div>
    </div>
  );
}
