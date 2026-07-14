import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { DataTable } from '@/components/data-table/DataTable';
import { useStockMovements } from '@/modules/inventory/hooks/useStock';
import type { InventoryItemResource, InventoryMovementResource } from '@/modules/inventory/types';

const MOVEMENT_BADGE: Record<InventoryMovementResource['attributes']['movement_type'], 'success' | 'destructive' | 'secondary'> = {
  inbound: 'success',
  outbound: 'destructive',
  adjustment: 'secondary',
};

export function MovementsDialog({
  open,
  onOpenChange,
  item,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  item?: InventoryItemResource;
}) {
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const productId = item?.attributes.product_id;
  const { data, isLoading, isError, refetch } = useStockMovements(open ? productId : undefined, cursor);

  const columns = useMemo<ColumnDef<InventoryMovementResource, unknown>[]>(
    () => [
      {
        id: 'created_at',
        header: 'Date',
        cell: ({ row }) => new Date(row.original.attributes.created_at).toLocaleString(),
      },
      {
        id: 'movement_type',
        header: 'Type',
        cell: ({ row }) => {
          const type = row.original.attributes.movement_type;
          return <Badge variant={MOVEMENT_BADGE[type]}>{type}</Badge>;
        },
      },
      {
        id: 'quantity',
        header: 'Quantity',
        cell: ({ row }) => {
          const q = row.original.attributes.quantity;
          return <span className={q < 0 ? 'text-destructive' : 'text-emerald-600'}>{q > 0 ? `+${q}` : q}</span>;
        },
      },
      {
        id: 'reason',
        header: 'Reason',
        cell: ({ row }) => row.original.attributes.reason ?? <span className="text-muted-foreground">—</span>,
      },
    ],
    []
  );

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        if (!next) setCursor(undefined);
        onOpenChange(next);
      }}
    >
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Movement history</DialogTitle>
          {item && (
            <DialogDescription>
              {item.attributes.product_name} ({item.attributes.product_sku})
            </DialogDescription>
          )}
        </DialogHeader>

        <DataTable
          columns={columns}
          data={data?.items ?? []}
          isLoading={isLoading}
          isError={isError}
          onRetry={() => refetch()}
          emptyTitle="No movements yet"
          pagination={{
            hasNext: !!data?.pagination.next_cursor,
            hasPrev: !!data?.pagination.prev_cursor,
            onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
            onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
          }}
        />
      </DialogContent>
    </Dialog>
  );
}
