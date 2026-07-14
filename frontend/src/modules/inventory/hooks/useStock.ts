import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { stockService } from '@/modules/inventory/services/stock';
import type {
  AdjustStockPayload,
  InventoryItemResource,
  InventoryMovementResource,
  StockListParams,
} from '@/modules/inventory/types';
import type { Page } from '@/types/api';

export const stockQueryKey = (filter: StockListParams) => ['inventory', 'stock', filter] as const;

export function useStock(filter: StockListParams = {}) {
  return useQuery<Page<InventoryItemResource>>({
    queryKey: stockQueryKey(filter),
    queryFn: () => stockService.list(filter),
  });
}

export function useStockItem(productId: string | undefined) {
  return useQuery<InventoryItemResource>({
    queryKey: ['inventory', 'stock-item', productId],
    queryFn: () => stockService.get(productId as string),
    enabled: !!productId,
  });
}

export function useStockMovements(productId: string | undefined, cursor?: string) {
  return useQuery<Page<InventoryMovementResource>>({
    queryKey: ['inventory', 'stock-movements', productId, cursor],
    queryFn: () => stockService.movements(productId as string, cursor),
    enabled: !!productId,
  });
}

export function useAdjustStock(productId: string) {
  const queryClient = useQueryClient();
  return useMutation<InventoryItemResource, unknown, AdjustStockPayload>({
    mutationFn: (payload) => stockService.adjust(productId, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] });
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-item', productId] });
      queryClient.invalidateQueries({ queryKey: ['inventory', 'stock-movements', productId] });
    },
  });
}
