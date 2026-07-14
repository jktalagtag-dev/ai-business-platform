import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { documentService } from '@/modules/kb/services/documents';
import type { KbDocumentListParams, KbDocumentResource } from '@/modules/kb/types';
import type { Page } from '@/types/api';

const POLL_INTERVAL_MS = 3000;

/** Exported standalone so the polling decision can be unit-tested without
 * mounting a real query. */
export function getKbPollInterval(data: Page<KbDocumentResource> | undefined): number | false {
  const hasProcessing = data?.items.some((doc) => doc.attributes.status === 'processing');
  return hasProcessing ? POLL_INTERVAL_MS : false;
}

/** Upload processing (extract/chunk/embed) runs in a background queue with
 * no push notification when it finishes, so this polls while any document
 * in the current page is still `processing` and stops once everything has
 * settled to `ready`/`failed`. */
export function useDocuments(params: KbDocumentListParams = {}) {
  return useQuery<Page<KbDocumentResource>>({
    queryKey: ['kb', 'documents', params],
    queryFn: () => documentService.list(params),
    refetchInterval: (query) =>
      getKbPollInterval(query.state.data as Page<KbDocumentResource> | undefined),
  });
}

export function useUploadDocument() {
  const queryClient = useQueryClient();
  return useMutation<KbDocumentResource, unknown, { file: File; title?: string }>({
    mutationFn: ({ file, title }) => documentService.upload(file, title),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['kb', 'documents'] }),
  });
}

export function useDeleteDocument() {
  const queryClient = useQueryClient();
  return useMutation<void, unknown, string>({
    mutationFn: (id) => documentService.remove(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['kb', 'documents'] }),
  });
}
