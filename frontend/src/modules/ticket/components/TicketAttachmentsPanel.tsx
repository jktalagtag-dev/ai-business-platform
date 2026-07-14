import { useRef } from 'react';
import { Loader2, Paperclip, Upload } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/layout/EmptyState';
import { ErrorState } from '@/components/layout/ErrorState';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useTicketAbilities } from '@/modules/ticket/hooks/useTicketAbilities';
import { useTicketAttachments, useUploadTicketAttachment } from '@/modules/ticket/hooks/useTicketAttachments';
import type { TicketResource } from '@/modules/ticket/types';

const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10MB — matches UploadTicketAttachmentRequest's max:10240 (KB)

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

/** Attachments have no delete endpoint, and `url` is an unsigned,
 * non-expiring public-disk link — anyone with the link can view the file
 * (a known backend gap, not something this UI can fix). */
export function TicketAttachmentsPanel({ ticket }: { ticket: TicketResource }) {
  const { canComment } = useTicketAbilities(ticket);
  const inputRef = useRef<HTMLInputElement>(null);
  const { data, isLoading, isError, refetch } = useTicketAttachments(ticket.id);
  const upload = useUploadTicketAttachment(ticket.id);

  function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    if (file.size > MAX_SIZE_BYTES) {
      toast.error('File is too large (max 10 MB).');
      return;
    }

    upload.mutate(file, {
      onSuccess: () => toast.success('Attachment uploaded.'),
      onError: (error) => toast.error(isApiError(error) ? error.message : 'Unable to upload attachment.'),
    });
  }

  return (
    <div className="space-y-4">
      {canComment && (
        <div>
          <input ref={inputRef} type="file" className="hidden" onChange={handleFileChange} />
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={upload.isPending}
            onClick={() => inputRef.current?.click()}
          >
            {upload.isPending ? <Loader2 className="animate-spin" /> : <Upload className="h-4 w-4" />}
            Upload attachment
          </Button>
        </div>
      )}

      {isError ? (
        <ErrorState message="Failed to load attachments." onRetry={() => refetch()} />
      ) : isLoading ? (
        <div className="flex items-center justify-center rounded-lg border p-6">
          <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
        </div>
      ) : (data?.items.length ?? 0) === 0 ? (
        <EmptyState title="No attachments yet" />
      ) : (
        <ul className="space-y-2">
          {data?.items.map((attachment) => (
            <li key={attachment.id} className="flex items-center justify-between rounded-lg border p-3 text-sm">
              <a
                href={attachment.attributes.url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 text-primary hover:underline"
              >
                <Paperclip className="h-4 w-4 shrink-0" />
                {attachment.attributes.original_filename}
              </a>
              <span className="text-xs text-muted-foreground">
                {formatSize(attachment.attributes.size_bytes)}
              </span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
