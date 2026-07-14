import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { History, SlidersHorizontal } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { useAbility } from '@/hooks/useAbility';
import { useStock } from '@/modules/inventory/hooks/useStock';
import { AdjustStockDialog } from '@/modules/inventory/components/AdjustStockDialog';
import { MovementsDialog } from '@/modules/inventory/components/MovementsDialog';
import type { InventoryItemResource } from '@/modules/inventory/types';

export function StockTab() {
  const canManage = useAbility('inventory.manage');

  const [lowStockOnly, setLowStockOnly] = useState(false);
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const { data, isLoading, isError, refetch } = useStock({
    low_stock: lowStockOnly || undefined,
    cursor,
  });

  const [adjusting, setAdjusting] = useState<InventoryItemResource | undefined>(undefined);
  const [viewingHistory, setViewingHistory] = useState<InventoryItemResource | undefined>(undefined);

  const columns = useMemo<ColumnDef<InventoryItemResource, unknown>[]>(
    () => [
      { id: 'sku', header: 'SKU', cell: ({ row }) => row.original.attributes.product_sku },
      { id: 'name', header: 'Product', cell: ({ row }) => row.original.attributes.product_name },
      {
        id: 'on_hand',
        header: 'On hand',
        cell: ({ row }) => row.original.attributes.quantity_on_hand,
      },
      {
        id: 'reserved',
        header: 'Reserved',
        cell: ({ row }) => row.original.attributes.quantity_reserved,
      },
      {
        id: 'reorder_point',
        header: 'Reorder point',
        cell: ({ row }) => row.original.attributes.reorder_point,
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) =>
          row.original.attributes.is_low_stock ? (
            <Badge variant="warning">Low stock</Badge>
          ) : (
            <Badge variant="secondary">OK</Badge>
          ),
      },
      {
        id: 'actions',
        header: '',
        cell: ({ row }) => (
          <div className="flex justify-end gap-1">
            <Button variant="ghost" size="icon" onClick={() => setViewingHistory(row.original)}>
              <History className="h-4 w-4" />
              <span className="sr-only">View history</span>
            </Button>
            {canManage && (
              <Button variant="ghost" size="icon" onClick={() => setAdjusting(row.original)}>
                <SlidersHorizontal className="h-4 w-4" />
                <span className="sr-only">Adjust stock</span>
              </Button>
            )}
          </div>
        ),
      },
    ],
    [canManage]
  );

  return (
    <div>
      <PageHeader title="Stock" description="Stock levels are tracked per product." />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No stock records found"
        toolbar={
          <label className="flex items-center gap-2 text-sm">
            <Checkbox
              checked={lowStockOnly}
              onCheckedChange={(checked) => {
                setCursor(undefined);
                setLowStockOnly(checked);
              }}
            />
            Low stock only
          </label>
        }
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      <AdjustStockDialog
        open={!!adjusting}
        onOpenChange={(open) => !open && setAdjusting(undefined)}
        item={adjusting}
      />
      <MovementsDialog
        open={!!viewingHistory}
        onOpenChange={(open) => !open && setViewingHistory(undefined)}
        item={viewingHistory}
      />
    </div>
  );
}
