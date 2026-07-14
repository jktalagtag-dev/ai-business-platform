import { api } from '@/lib/api-client';
import type { ProductListParams, ProductPayload, ProductResource } from '@/modules/inventory/types';

export const productService = {
  list: (params: ProductListParams = {}) =>
    api.getPage<ProductResource>('/products', { query: { ...params } }),

  get: (id: string) => api.get<ProductResource>(`/products/${id}`),

  create: (payload: ProductPayload) => api.post<ProductResource>('/products', payload),

  update: (id: string, payload: ProductPayload) =>
    api.patch<ProductResource>(`/products/${id}`, payload),

  remove: (id: string) => api.delete<void>(`/products/${id}`),
};
