import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { useNavigate } from 'react-router-dom';
import { MessageSquarePlus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import {
  useConversations,
  useCreateConversation,
  useDeleteConversation,
} from '@/modules/ai/hooks/useConversations';
import { paths } from '@/routes/routes.config';
import type { AiConversationResource } from '@/modules/ai/types';

export function ConversationsListPage() {
  const navigate = useNavigate();
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useConversations({ cursor });
  const createConversation = useCreateConversation();
  const deleteConversation = useDeleteConversation();
  const [deleting, setDeleting] = useState<AiConversationResource | undefined>(undefined);

  function handleNewChat() {
    createConversation.mutate(
      {},
      {
        onSuccess: (conversation) => navigate(`${paths.aiConversations}/${conversation.id}`),
        onError: (error) =>
          toast.error(isApiError(error) ? error.message : 'Unable to start a new conversation.'),
      }
    );
  }

  const columns = useMemo<ColumnDef<AiConversationResource, unknown>[]>(
    () => [
      {
        id: 'title',
        header: 'Conversation',
        cell: ({ row }) => row.original.attributes.title || 'Untitled conversation',
      },
      { id: 'model', header: 'Model', cell: ({ row }) => row.original.attributes.model },
      {
        id: 'tokens',
        header: 'Tokens used',
        cell: ({ row }) =>
          row.original.attributes.total_prompt_tokens + row.original.attributes.total_completion_tokens,
      },
      {
        id: 'updated_at',
        header: 'Last activity',
        cell: ({ row }) => new Date(row.original.attributes.updated_at).toLocaleString(),
      },
      {
        id: 'actions',
        header: '',
        cell: ({ row }) => (
          <div className="flex justify-end">
            <Button
              variant="ghost"
              size="icon"
              onClick={(e) => {
                e.stopPropagation();
                setDeleting(row.original);
              }}
            >
              <Trash2 className="h-4 w-4" />
              <span className="sr-only">Delete</span>
            </Button>
          </div>
        ),
      },
    ],
    []
  );

  return (
    <div>
      <PageHeader
        title="AI Assistant"
        actions={
          <Button onClick={handleNewChat} disabled={createConversation.isPending}>
            <MessageSquarePlus className="h-4 w-4" />
            New chat
          </Button>
        }
      />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No conversations yet"
        emptyDescription="Start a new chat to ask the assistant a question."
        onRowClick={(conversation) => navigate(`${paths.aiConversations}/${conversation.id}`)}
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      <ConfirmDialog
        open={!!deleting}
        onOpenChange={(open) => !open && setDeleting(undefined)}
        title="Delete conversation?"
        description={`"${deleting?.attributes.title || 'Untitled conversation'}" and its full message history will be permanently removed.`}
        confirmLabel="Delete"
        isLoading={deleteConversation.isPending}
        onConfirm={() => {
          if (!deleting) return;
          deleteConversation.mutate(deleting.id, {
            onSuccess: () => {
              toast.success('Conversation deleted.');
              setDeleting(undefined);
            },
            onError: () => toast.error('Unable to delete conversation.'),
          });
        }}
      />
    </div>
  );
}
