import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { supplierService } from '@/modules/inventory/services/suppliers';
import type {
  SupplierListParams,
  SupplierPayload,
  SupplierResource,
} from '@/modules/inventory/types';
import type { Page } from '@/types/api';

export const suppliersQueryKey = (filter: SupplierListParams) =>
  ['inventory', 'suppliers', filter] as const;

export function useSuppliers(filter: SupplierListParams = {}) {
  return useQuery<Page<SupplierResource>>({
    queryKey: suppliersQueryKey(filter),
    queryFn: () => supplierService.list(filter),
  });
}

export function useSupplier(id: string | undefined) {
  return useQuery<SupplierResource>({
    queryKey: ['inventory', 'supplier', id],
    queryFn: () => supplierService.get(id as string),
    enabled: !!id,
  });
}

function useInvalidateSuppliers() {
  const queryClient = useQueryClient();
  return () => queryClient.invalidateQueries({ queryKey: ['inventory', 'suppliers'] });
}

export function useCreateSupplier() {
  const invalidate = useInvalidateSuppliers();
  return useMutation<SupplierResource, unknown, SupplierPayload>({
    mutationFn: (payload) => supplierService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateSupplier(id: string) {
  const invalidate = useInvalidateSuppliers();
  return useMutation<SupplierResource, unknown, SupplierPayload>({
    mutationFn: (payload) => supplierService.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteSupplier() {
  const invalidate = useInvalidateSuppliers();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => supplierService.remove(id),
    onSuccess: invalidate,
  });
}
