import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { categoryService } from '@/modules/inventory/services/categories';
import type { CategoryPayload, CategoryResource } from '@/modules/inventory/types';
import type { Page } from '@/types/api';

export const categoriesQueryKey = (cursor?: string) => ['inventory', 'categories', { cursor }] as const;

export function useCategories(cursor?: string) {
  return useQuery<Page<CategoryResource>>({
    queryKey: categoriesQueryKey(cursor),
    queryFn: () => categoryService.list({ cursor }),
  });
}

export function useCategory(id: string | undefined) {
  return useQuery<CategoryResource>({
    queryKey: ['inventory', 'category', id],
    queryFn: () => categoryService.get(id as string),
    enabled: !!id,
  });
}

function useInvalidateCategories() {
  const queryClient = useQueryClient();
  return () => queryClient.invalidateQueries({ queryKey: ['inventory', 'categories'] });
}

export function useCreateCategory() {
  const invalidate = useInvalidateCategories();
  return useMutation<CategoryResource, unknown, CategoryPayload>({
    mutationFn: (payload) => categoryService.create(payload),
    onSuccess: invalidate,
  });
}

export function useUpdateCategory(id: string) {
  const invalidate = useInvalidateCategories();
  return useMutation<CategoryResource, unknown, CategoryPayload>({
    mutationFn: (payload) => categoryService.update(id, payload),
    onSuccess: invalidate,
  });
}

export function useDeleteCategory() {
  const invalidate = useInvalidateCategories();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => categoryService.remove(id),
    onSuccess: invalidate,
  });
}
