import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { useAbility } from '@/hooks/useAbility';
import { useCategories, useDeleteCategory } from '@/modules/inventory/hooks/useCategories';
import { CategoryFormDialog } from '@/modules/inventory/components/CategoryFormDialog';
import type { CategoryResource } from '@/modules/inventory/types';

export function CategoriesTab() {
  const canManage = useAbility('categories.manage');
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useCategories(cursor);

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<CategoryResource | undefined>(undefined);
  const [deleting, setDeleting] = useState<CategoryResource | undefined>(undefined);
  const deleteCategory = useDeleteCategory();

  const nameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const item of data?.items ?? []) map.set(item.id, item.attributes.name);
    return map;
  }, [data]);

  const columns = useMemo<ColumnDef<CategoryResource, unknown>[]>(
    () => [
      { id: 'name', header: 'Name', cell: ({ row }) => row.original.attributes.name },
      {
        id: 'parent',
        header: 'Parent',
        cell: ({ row }) => {
          const parentId = row.original.attributes.parent_category_id;
          if (!parentId) return <span className="text-muted-foreground">—</span>;
          return nameById.get(parentId) ?? <span className="text-muted-foreground">—</span>;
        },
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: CategoryResource } }) => (
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
            } satisfies ColumnDef<CategoryResource, unknown>,
          ]
        : []),
    ],
    [canManage, nameById]
  );

  return (
    <div>
      <PageHeader
        title="Categories"
        description="Organize products into categories."
        actions={
          canManage && (
            <Button
              onClick={() => {
                setEditing(undefined);
                setFormOpen(true);
              }}
            >
              <Plus className="h-4 w-4" />
              New category
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
        emptyTitle="No categories yet"
        emptyDescription={canManage ? 'Create your first category to organize products.' : undefined}
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <>
          <CategoryFormDialog open={formOpen} onOpenChange={setFormOpen} category={editing} />
          <ConfirmDialog
            open={!!deleting}
            onOpenChange={(open) => !open && setDeleting(undefined)}
            title="Delete category?"
            description={`"${deleting?.attributes.name}" will be permanently removed.`}
            confirmLabel="Delete"
            isLoading={deleteCategory.isPending}
            onConfirm={() => {
              if (!deleting) return;
              deleteCategory.mutate(deleting.id, {
                onSuccess: () => {
                  toast.success('Category deleted.');
                  setDeleting(undefined);
                },
                onError: () => toast.error('Unable to delete category.'),
              });
            }}
          />
        </>
      )}
    </div>
  );
}
