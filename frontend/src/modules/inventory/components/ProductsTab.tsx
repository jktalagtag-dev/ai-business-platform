import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { useAbility } from '@/hooks/useAbility';
import { useCategories } from '@/modules/inventory/hooks/useCategories';
import { useDeleteProduct, useProducts } from '@/modules/inventory/hooks/useProducts';
import { useStock } from '@/modules/inventory/hooks/useStock';
import { ProductFormDialog } from '@/modules/inventory/components/ProductFormDialog';
import type { ProductResource } from '@/modules/inventory/types';

const ALL_CATEGORIES = '__all__';
const ALL_STATUS = '__all__';

export function ProductsTab() {
  const canManage = useAbility('products.manage');
  const { data: categoriesPage } = useCategories();
  const { data: stockPage } = useStock();

  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState(ALL_CATEGORIES);
  const [isActive, setIsActive] = useState(ALL_STATUS);
  const [cursor, setCursor] = useState<string | undefined>(undefined);

  const filter = {
    search: search || undefined,
    category_id: categoryId === ALL_CATEGORIES ? undefined : categoryId,
    is_active: isActive === ALL_STATUS ? undefined : isActive === 'active',
    cursor,
  };
  const { data, isLoading, isError, refetch } = useProducts(filter);

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<ProductResource | undefined>(undefined);
  const [deleting, setDeleting] = useState<ProductResource | undefined>(undefined);
  const deleteProduct = useDeleteProduct();

  const categoryNameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const item of categoriesPage?.items ?? []) map.set(item.id, item.attributes.name);
    return map;
  }, [categoriesPage]);

  /** Stock lists server-fixed per_page=25 with no way to request more — same
   * known first-page-only limitation already accepted for the Manager/
   * Department/Position pickers elsewhere in this module. */
  const quantityByProductId = useMemo(() => {
    const map = new Map<string, number>();
    for (const item of stockPage?.items ?? []) map.set(item.attributes.product_id, item.attributes.quantity_on_hand);
    return map;
  }, [stockPage]);

  function resetToFirstPage<T>(setter: (v: T) => void) {
    return (v: T) => {
      setCursor(undefined);
      setter(v);
    };
  }

  const columns = useMemo<ColumnDef<ProductResource, unknown>[]>(
    () => [
      { id: 'sku', header: 'SKU', cell: ({ row }) => row.original.attributes.sku },
      { id: 'name', header: 'Name', cell: ({ row }) => row.original.attributes.name },
      {
        id: 'category',
        header: 'Category',
        cell: ({ row }) => {
          const id = row.original.attributes.category_id;
          if (!id) return <span className="text-muted-foreground">—</span>;
          return categoryNameById.get(id) ?? <span className="text-muted-foreground">—</span>;
        },
      },
      {
        id: 'quantity',
        header: 'On hand',
        cell: ({ row }) => quantityByProductId.get(row.original.id) ?? <span className="text-muted-foreground">—</span>,
      },
      {
        id: 'unit_price',
        header: 'Unit price',
        cell: ({ row }) => `$${Number(row.original.attributes.unit_price).toFixed(2)}`,
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) =>
          row.original.attributes.is_active ? (
            <Badge variant="success">Active</Badge>
          ) : (
            <Badge variant="secondary">Inactive</Badge>
          ),
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: ProductResource } }) => (
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
            } satisfies ColumnDef<ProductResource, unknown>,
          ]
        : []),
    ],
    [canManage, categoryNameById, quantityByProductId]
  );

  return (
    <div>
      <PageHeader
        title="Products"
        actions={
          canManage && (
            <Button
              onClick={() => {
                setEditing(undefined);
                setFormOpen(true);
              }}
            >
              <Plus className="h-4 w-4" />
              New product
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
        emptyTitle="No products found"
        toolbar={
          <>
            <Input
              placeholder="Search by name or SKU…"
              value={search}
              onChange={(e) => resetToFirstPage(setSearch)(e.target.value)}
              className="sm:max-w-xs"
            />
            <Select value={categoryId} onValueChange={resetToFirstPage(setCategoryId)}>
              <SelectTrigger className="sm:w-48">
                <SelectValue placeholder="All categories" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_CATEGORIES}>All categories</SelectItem>
                {(categoriesPage?.items ?? []).map((c) => (
                  <SelectItem key={c.id} value={c.id}>
                    {c.attributes.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={isActive} onValueChange={resetToFirstPage(setIsActive)}>
              <SelectTrigger className="sm:w-40">
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_STATUS}>All statuses</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
              </SelectContent>
            </Select>
          </>
        }
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <>
          <ProductFormDialog open={formOpen} onOpenChange={setFormOpen} product={editing} />
          <ConfirmDialog
            open={!!deleting}
            onOpenChange={(open) => !open && setDeleting(undefined)}
            title="Delete product?"
            description={`"${deleting?.attributes.name}" will be permanently removed.`}
            confirmLabel="Delete"
            isLoading={deleteProduct.isPending}
            onConfirm={() => {
              if (!deleting) return;
              deleteProduct.mutate(deleting.id, {
                onSuccess: () => {
                  toast.success('Product deleted.');
                  setDeleting(undefined);
                },
                onError: () => toast.error('Unable to delete product.'),
              });
            }}
          />
        </>
      )}
    </div>
  );
}
