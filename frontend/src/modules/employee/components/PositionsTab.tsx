import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { useAbility } from '@/hooks/useAbility';
import { useDeletePosition, usePositions } from '@/modules/employee/hooks/usePositions';
import { PositionFormDialog } from '@/modules/employee/components/PositionFormDialog';
import type { PositionResource } from '@/modules/employee/types';

export function PositionsTab() {
  const canManage = useAbility('positions.manage');
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = usePositions(cursor);

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<PositionResource | undefined>(undefined);
  const [deleting, setDeleting] = useState<PositionResource | undefined>(undefined);
  const deletePosition = useDeletePosition();

  const columns = useMemo<ColumnDef<PositionResource, unknown>[]>(
    () => [
      { id: 'title', header: 'Title', cell: ({ row }) => row.original.attributes.title },
      {
        id: 'description',
        header: 'Description',
        cell: ({ row }) => row.original.attributes.description ?? <span className="text-muted-foreground">—</span>,
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: PositionResource } }) => (
                <div className="flex justify-end gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                      setEditing(row.original);
                      setFormOpen(true);
                    }}
                  >
                    <Pencil className="h-4 w-4" />
                    <span className="sr-only">Edit</span>
                  </Button>
                  <Button variant="ghost" size="icon" onClick={() => setDeleting(row.original)}>
                    <Trash2 className="h-4 w-4" />
                    <span className="sr-only">Delete</span>
                  </Button>
                </div>
              ),
            } satisfies ColumnDef<PositionResource, unknown>,
          ]
        : []),
    ],
    [canManage]
  );

  return (
    <div>
      <PageHeader
        title="Positions"
        description="Job titles used across your organization."
        actions={
          canManage && (
            <Button
              onClick={() => {
                setEditing(undefined);
                setFormOpen(true);
              }}
            >
              <Plus className="h-4 w-4" />
              New position
            </Button>
          )
        }
      />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No positions yet"
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <>
          <PositionFormDialog open={formOpen} onOpenChange={setFormOpen} position={editing} />
          <ConfirmDialog
            open={!!deleting}
            onOpenChange={(open) => !open && setDeleting(undefined)}
            title="Delete position?"
            description={`"${deleting?.attributes.title}" will be permanently removed.`}
            confirmLabel="Delete"
            isLoading={deletePosition.isPending}
            onConfirm={() => {
              if (!deleting) return;
              deletePosition.mutate(deleting.id, {
                onSuccess: () => {
                  toast.success('Position deleted.');
                  setDeleting(undefined);
                },
                onError: () => toast.error('Unable to delete position.'),
              });
            }}
          />
        </>
      )}
    </div>
  );
}
