import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { toast } from '@/components/ui/sonner';
import { useAbility } from '@/hooks/useAbility';
import { useDeleteDocument, useDocuments } from '@/modules/kb/hooks/useDocuments';
import { DocumentStatusBadge } from '@/modules/kb/components/DocumentStatusBadge';
import type { KbDocumentResource } from '@/modules/kb/types';

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function DocumentsTable() {
  const canManage = useAbility('knowledge_base.manage');
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useDocuments({ cursor });
  const deleteDocument = useDeleteDocument();
  const [deleting, setDeleting] = useState<KbDocumentResource | undefined>(undefined);

  const columns = useMemo<ColumnDef<KbDocumentResource, unknown>[]>(
    () => [
      {
        id: 'title',
        header: 'Document',
        cell: ({ row }) => (
          <div>
            <div>{row.original.attributes.title}</div>
            {row.original.attributes.status === 'failed' && row.original.attributes.error_message && (
              <div className="mt-0.5 text-xs text-destructive">
                {row.original.attributes.error_message}
              </div>
            )}
          </div>
        ),
      },
      {
        id: 'size',
        header: 'Size',
        cell: ({ row }) => formatSize(row.original.attributes.size_bytes),
      },
      {
        id: 'pages',
        header: 'Pages',
        cell: ({ row }) => row.original.attributes.page_count ?? '—',
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => <DocumentStatusBadge status={row.original.attributes.status} />,
      },
      {
        id: 'created_at',
        header: 'Uploaded',
        cell: ({ row }) => new Date(row.original.attributes.created_at).toLocaleString(),
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: KbDocumentResource } }) => (
                <div className="flex justify-end">
                  <Button variant="ghost" size="icon" onClick={() => setDeleting(row.original)}>
                    <Trash2 className="h-4 w-4" />
                    <span className="sr-only">Delete</span>
                  </Button>
                </div>
              ),
            } satisfies ColumnDef<KbDocumentResource, unknown>,
          ]
        : []),
    ],
    [canManage]
  );

  return (
    <div>
      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No documents yet"
        emptyDescription={canManage ? 'Upload a PDF above to build the knowledge base.' : undefined}
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <ConfirmDialog
          open={!!deleting}
          onOpenChange={(open) => !open && setDeleting(undefined)}
          title="Delete document?"
          description={`"${deleting?.attributes.title}" and its extracted content will be permanently removed.`}
          confirmLabel="Delete"
          isLoading={deleteDocument.isPending}
          onConfirm={() => {
            if (!deleting) return;
            deleteDocument.mutate(deleting.id, {
              onSuccess: () => {
                toast.success('Document deleted.');
                setDeleting(undefined);
              },
              onError: () => toast.error('Unable to delete document.'),
            });
          }}
        />
      )}
    </div>
  );
}
