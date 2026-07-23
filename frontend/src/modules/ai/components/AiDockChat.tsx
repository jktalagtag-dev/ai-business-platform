import { useEffect, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { AlertTriangle, ArrowRight, Loader2, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import {
  useConversationMessages,
  useConversations,
  useCreateConversation,
} from '@/modules/ai/hooks/useConversations';
import { useChatStream } from '@/modules/ai/hooks/useChatStream';
import { messageResourceToChatEntry } from '@/modules/ai/mapMessage';
import { ChatComposer } from '@/modules/ai/components/ChatComposer';
import { ChatMessageBubble } from '@/modules/ai/components/ChatMessageBubble';
import { paths } from '@/routes/routes.config';

const SUGGESTIONS = [
  'Summarize open tickets by priority',
  'Which products are running low on stock?',
  'List employees on leave this week',
  'Search the knowledge base for onboarding docs',
];

/**
 * The dock's chat surface — reuses the exact same hooks and message/composer
 * components as the full-page `/ai/conversations/:id` view (this is
 * deliberately not a separate chat implementation). It targets whichever
 * conversation was most recently active (the API's default list order),
 * creating a fresh one lazily on the dock's first-ever message rather than
 * up front, so opening the dock never creates an empty conversation nobody
 * asked for.
 *
 * Only mounted while the dock is open (see AiDockPanel) — closing the dock
 * unmounts this and drops its component state, so reopening it re-resolves
 * "most recent conversation" from scratch instead of holding a stale id.
 */
export function AiDockChat() {
  const { data: recentConversations } = useConversations({ per_page: 1 });
  const [activeConversationId, setActiveConversationId] = useState<string | undefined>(undefined);
  const [pendingMessage, setPendingMessage] = useState<string | null>(null);
  const createConversation = useCreateConversation();

  useEffect(() => {
    if (!activeConversationId && recentConversations?.items[0]) {
      setActiveConversationId(recentConversations.items[0].id);
    }
  }, [recentConversations, activeConversationId]);

  const { data: history, isLoading: isHistoryLoading } = useConversationMessages(activeConversationId);
  const { liveEntries, isStreaming, streamError, sendMessage } = useChatStream(activeConversationId ?? '');

  // A message sent before any conversation exists is held here until the
  // just-created conversation's id comes back, then replayed once.
  useEffect(() => {
    if (activeConversationId && pendingMessage) {
      sendMessage(pendingMessage);
      setPendingMessage(null);
    }
  }, [activeConversationId, pendingMessage, sendMessage]);

  const entries = useMemo(() => {
    const persisted = (history?.items ?? []).map(messageResourceToChatEntry);
    return [...persisted, ...liveEntries];
  }, [history, liveEntries]);

  const bottomRef = useRef<HTMLDivElement>(null);
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [entries.length, liveEntries]);

  function handleSend(content: string) {
    if (activeConversationId) {
      sendMessage(content);
      return;
    }
    setPendingMessage(content);
    createConversation.mutate(
      {},
      {
        onSuccess: (conversation) => setActiveConversationId(conversation.id),
        onError: (error) => {
          setPendingMessage(null);
          toast.error(isApiError(error) ? error.message : 'Unable to start a new conversation.');
        },
      }
    );
  }

  const isBusy = isStreaming || createConversation.isPending;
  const isLoadingHistory = !!activeConversationId && isHistoryLoading;

  return (
    <div className="flex flex-1 flex-col overflow-hidden">
      <div className="flex-1 space-y-3 overflow-y-auto p-4">
        {isLoadingHistory ? (
          <div className="flex items-center justify-center p-8">
            <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
          </div>
        ) : entries.length === 0 ? (
          <div className="flex h-full flex-col items-center justify-center gap-4 py-8 text-center">
            <Sparkles className="h-8 w-8 text-muted-foreground" strokeWidth={1.75} />
            <div>
              <p className="text-sm font-medium">Ask me anything</p>
              <p className="mt-1 text-xs text-muted-foreground">
                I can look up your tickets, inventory, employees, and more.
              </p>
            </div>
            <div className="flex flex-wrap justify-center gap-2">
              {SUGGESTIONS.map((suggestion) => (
                <button
                  key={suggestion}
                  type="button"
                  onClick={() => handleSend(suggestion)}
                  disabled={isBusy}
                  className="rounded-full border px-3 py-1.5 text-xs transition-colors duration-150 hover:bg-accent disabled:pointer-events-none disabled:opacity-50"
                >
                  {suggestion}
                </button>
              ))}
            </div>
          </div>
        ) : (
          entries.map((entry) => <ChatMessageBubble key={entry.id} entry={entry} />)
        )}
        <div ref={bottomRef} />
      </div>

      {streamError && (
        <div className="mx-4 mb-2 flex items-center gap-2 rounded-lg border border-destructive/50 bg-destructive/10 px-3 py-2 text-xs text-destructive">
          <AlertTriangle className="h-4 w-4 shrink-0" />
          {streamError}
        </div>
      )}

      <div className="px-4 pb-4">
        <ChatComposer disabled={isBusy} onSend={handleSend} />
        {activeConversationId && (
          <Button variant="link" size="sm" className="mt-1 h-auto px-0 text-xs" asChild>
            <Link to={`${paths.aiConversations}/${activeConversationId}`}>
              Open full conversation
              <ArrowRight className="h-3 w-3" />
            </Link>
          </Button>
        )}
      </div>
    </div>
  );
}
