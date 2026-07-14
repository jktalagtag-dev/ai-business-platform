import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormControl, FormField, FormItem, FormMessage } from '@/components/ui/form';
import { EmptyState } from '@/components/layout/EmptyState';
import { ErrorState } from '@/components/layout/ErrorState';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useCreateTicketComment, useTicketComments } from '@/modules/ticket/hooks/useTicketComments';
import { useTicketAbilities } from '@/modules/ticket/hooks/useTicketAbilities';
import { ticketCommentSchema, type TicketCommentFormValues } from '@/modules/ticket/forms/schemas';
import type { TicketResource } from '@/modules/ticket/types';

/** Mirrors the backend's `addComment`/`addInternalNote` policies
 * client-side: only the requester, the assigned technician, or a
 * tickets.manage holder can comment at all; only the latter two can mark a
 * comment internal (hidden from the requester). Showing controls that would
 * just get rejected server-side is worse than not showing them. */
export function TicketCommentsPanel({ ticket }: { ticket: TicketResource }) {
  const { canComment, canAddInternalNote } = useTicketAbilities(ticket);

  const { data, isLoading, isError, refetch } = useTicketComments(ticket.id);
  const createComment = useCreateTicketComment(ticket.id);

  const form = useForm<TicketCommentFormValues>({
    resolver: zodResolver(ticketCommentSchema),
    defaultValues: { body: '', is_internal: false },
  });

  const onSubmit = form.handleSubmit((values) => {
    createComment.mutate(
      { body: values.body, is_internal: canAddInternalNote ? values.is_internal : false },
      {
        onSuccess: () => form.reset({ body: '', is_internal: false }),
        onError: (error) => toast.error(isApiError(error) ? error.message : 'Unable to add comment.'),
      }
    );
  });

  return (
    <div className="space-y-4">
      {canComment && (
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-2" noValidate>
            <FormField
              control={form.control}
              name="body"
              render={({ field }) => (
                <FormItem>
                  <FormControl>
                    <Textarea rows={3} placeholder="Add a comment…" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="flex items-center justify-between">
              {canAddInternalNote ? (
                <FormField
                  control={form.control}
                  name="is_internal"
                  render={({ field }) => (
                    <FormItem className="flex flex-row items-center gap-2 space-y-0">
                      <FormControl>
                        <Checkbox checked={field.value} onCheckedChange={field.onChange} />
                      </FormControl>
                      <span className="text-sm text-muted-foreground">
                        Internal note (hidden from the requester)
                      </span>
                    </FormItem>
                  )}
                />
              ) : (
                <span />
              )}
              <Button type="submit" size="sm" disabled={createComment.isPending}>
                {createComment.isPending && <Loader2 className="animate-spin" />}
                Add comment
              </Button>
            </div>
          </form>
        </Form>
      )}

      {isError ? (
        <ErrorState message="Failed to load comments." onRetry={() => refetch()} />
      ) : isLoading ? (
        <div className="flex items-center justify-center rounded-lg border p-6">
          <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
        </div>
      ) : (data?.items.length ?? 0) === 0 ? (
        <EmptyState title="No comments yet" />
      ) : (
        <ul className="space-y-3">
          {data?.items.map((comment) => (
            <li key={comment.id} className="rounded-lg border p-3 text-sm">
              <div className="mb-1 flex items-center justify-between">
                <span className="text-xs text-muted-foreground">
                  {new Date(comment.attributes.created_at).toLocaleString()}
                </span>
                {comment.attributes.is_internal && <Badge variant="warning">Internal</Badge>}
              </div>
              <p className="whitespace-pre-wrap">{comment.attributes.body}</p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
