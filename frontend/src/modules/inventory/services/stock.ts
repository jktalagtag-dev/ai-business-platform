import { api } from '@/lib/api-client';
import type {
  AdjustStockPayload,
  InventoryItemResource,
  InventoryMovementResource,
  StockListParams,
} from '@/modules/inventory/types';

/** Stock endpoints are keyed by product id — there is no separate stock-record id. */
export const stockService = {
  list: (params: StockListParams = {}) =>
    api.getPage<InventoryItemResource>('/stock', { query: { ...params } }),

  get: (productId: string) => api.get<InventoryItemResource>(`/stock/${productId}`),

  adjust: (productId: string, payload: AdjustStockPayload) =>
    api.post<InventoryItemResource>(`/stock/${productId}/adjust`, payload),

  movements: (productId: string, cursor?: string) =>
    api.getPage<InventoryMovementResource>(`/stock/${productId}/movements`, {
      query: { cursor },
    }),
};
