import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { AlertTriangle, Loader2, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { PageHeader } from '@/components/layout/PageHeader';
import { ErrorState } from '@/components/layout/ErrorState';
import { EmptyState } from '@/components/layout/EmptyState';
import { toast } from '@/components/ui/sonner';
import {
  useConversation,
  useConversationMessages,
  useDeleteConversation,
} from '@/modules/ai/hooks/useConversations';
import { useChatStream } from '@/modules/ai/hooks/useChatStream';
import { messageResourceToChatEntry } from '@/modules/ai/mapMessage';
import { ChatComposer } from '@/modules/ai/components/ChatComposer';
import { ChatMessageBubble } from '@/modules/ai/components/ChatMessageBubble';
import { paths } from '@/routes/routes.config';

export function ConversationDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();

  const { data: conversation, isLoading: isConversationLoading, isError: isConversationError } =
    useConversation(id);
  const { data: history, isLoading: isHistoryLoading, isError: isHistoryError, refetch } =
    useConversationMessages(id);
  const { liveEntries, isStreaming, streamError, sendMessage } = useChatStream(id ?? '');
  const deleteConversation = useDeleteConversation();
  const [deleteOpen, setDeleteOpen] = useState(false);

  const entries = useMemo(() => {
    const persisted = (history?.items ?? []).map(messageResourceToChatEntry);
    return [...persisted, ...liveEntries];
  }, [history, liveEntries]);

  const bottomRef = useRef<HTMLDivElement>(null);
  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [entries.length, liveEntries]);

  if (isConversationLoading || isHistoryLoading) {
    return (
      <div className="flex items-center justify-center rounded-lg border p-12">
        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (isConversationError || isHistoryError || !conversation) {
    return <ErrorState message="Conversation not found." onRetry={() => refetch()} />;
  }

  return (
    <div className="flex h-[calc(100vh-8rem)] flex-col">
      <PageHeader
        title={conversation.attributes.title || 'Untitled conversation'}
        description={`${conversation.attributes.model} · ${
          conversation.attributes.total_prompt_tokens + conversation.attributes.total_completion_tokens
        } tokens used`}
        actions={
          <Button variant="outline" onClick={() => setDeleteOpen(true)}>
            <Trash2 className="h-4 w-4" />
            Delete
          </Button>
        }
      />

      <div className="flex-1 space-y-3 overflow-y-auto rounded-lg border p-4">
        {entries.length === 0 ? (
          <EmptyState title="No messages yet" description="Ask the assistant something to get started." />
        ) : (
          entries.map((entry) => <ChatMessageBubble key={entry.id} entry={entry} />)
        )}
        <div ref={bottomRef} />
      </div>

      {streamError && (
        <div className="mt-3 flex items-center gap-2 rounded-lg border border-destructive/50 bg-destructive/10 px-3 py-2 text-sm text-destructive">
          <AlertTriangle className="h-4 w-4 shrink-0" />
          {streamError}
        </div>
      )}

      <div className="mt-3">
        <ChatComposer disabled={isStreaming} onSend={sendMessage} />
      </div>

      <ConfirmDialog
        open={deleteOpen}
        onOpenChange={setDeleteOpen}
        title="Delete conversation?"
        description="This conversation and its full message history will be permanently removed."
        confirmLabel="Delete"
        isLoading={deleteConversation.isPending}
        onConfirm={() => {
          if (!id) return;
          deleteConversation.mutate(id, {
            onSuccess: () => {
              toast.success('Conversation deleted.');
              navigate(paths.aiConversations);
            },
            onError: () => toast.error('Unable to delete conversation.'),
          });
        }}
      />
    </div>
  );
}
