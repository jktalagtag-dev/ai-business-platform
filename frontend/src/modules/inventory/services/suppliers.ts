import { api } from '@/lib/api-client';
import type {
  SupplierListParams,
  SupplierPayload,
  SupplierResource,
} from '@/modules/inventory/types';

export const supplierService = {
  list: (params: SupplierListParams = {}) =>
    api.getPage<SupplierResource>('/suppliers', { query: { ...params } }),

  get: (id: string) => api.get<SupplierResource>(`/suppliers/${id}`),

  create: (payload: SupplierPayload) => api.post<SupplierResource>('/suppliers', payload),

  update: (id: string, payload: SupplierPayload) =>
    api.patch<SupplierResource>(`/suppliers/${id}`, payload),

  remove: (id: string) => api.delete<void>(`/suppliers/${id}`),
};
