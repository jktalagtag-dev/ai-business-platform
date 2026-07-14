import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { productService } from '@/modules/inventory/services/products';
import type { ProductListParams, ProductPayload, ProductResource } from '@/modules/inventory/types';
import type { Page } from '@/types/api';

export const productsQueryKey = (filter: ProductListParams) => ['inventory', 'products', filter] as const;

export function useProducts(filter: ProductListParams = {}) {
  return useQuery<Page<ProductResource>>({
    queryKey: productsQueryKey(filter),
    queryFn: () => productService.list(filter),
  });
}

export function useProduct(id: string | undefined) {
  return useQuery<ProductResource>({
    queryKey: ['inventory', 'product', id],
    queryFn: () => productService.get(id as string),
    enabled: !!id,
  });
}

function useInvalidateProducts() {
  const queryClient = useQueryClient();
  return () => {
    queryClient.invalidateQueries({ queryKey: ['inventory', 'products'] });
    // Creating/editing a product can also affect its auto-provisioned stock row.
    queryClient.invalidateQueries({ queryKey: ['inventory', 'stock'] });
  };
}

export function useCreateProduct() {
  const invalidate = useInvalidateProducts();
  return useMutation<ProductResource, unknown, ProductPayload>({
    mutationFn: (payload) => productService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateProduct(id: string) {
  const invalidate = useInvalidateProducts();
  return useMutation<ProductResource, unknown, ProductPayload>({
    mutationFn: (payload) => productService.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteProduct() {
  const invalidate = useInvalidateProducts();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => productService.remove(id),
    onSuccess: invalidate,
  });
}
