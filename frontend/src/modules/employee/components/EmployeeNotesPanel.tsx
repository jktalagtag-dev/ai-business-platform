import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormControl, FormField, FormItem, FormMessage } from '@/components/ui/form';
import { EmptyState } from '@/components/layout/EmptyState';
import { ErrorState } from '@/components/layout/ErrorState';
import { toast } from '@/components/ui/sonner';
import { isApiError } from '@/lib/errors';
import { useCreateEmployeeNote, useEmployeeNotes } from '@/modules/employee/hooks/useEmployeeNotes';
import { employeeNoteSchema, type EmployeeNoteFormValues } from '@/modules/employee/forms/schemas';

/** Create + list only — the backend has no note edit/delete endpoint.
 * Rendered when the actor has `employees.manage`; department managers can
 * also add notes per the backend policy, but that can't be determined
 * client-side without an extra request, so this panel is only shown to
 * manage-capable actors here. */
export function EmployeeNotesPanel({ employeeId }: { employeeId: string }) {
  const { data, isLoading, isError, refetch } = useEmployeeNotes(employeeId);
  const createNote = useCreateEmployeeNote(employeeId);

  const form = useForm<EmployeeNoteFormValues>({
    resolver: zodResolver(employeeNoteSchema),
    defaultValues: { note: '' },
  });

  const onSubmit = form.handleSubmit((values) => {
    createNote.mutate(values, {
      onSuccess: () => form.reset({ note: '' }),
      onError: (error) => toast.error(isApiError(error) ? error.message : 'Unable to add note.'),
    });
  });

  return (
    <div className="space-y-4">
      <Form {...form}>
        <form onSubmit={onSubmit} className="space-y-2" noValidate>
          <FormField
            control={form.control}
            name="note"
            render={({ field }) => (
              <FormItem>
                <FormControl>
                  <Textarea rows={3} placeholder="Add a note…" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          <div className="flex justify-end">
            <Button type="submit" size="sm" disabled={createNote.isPending}>
              {createNote.isPending && <Loader2 className="animate-spin" />}
              Add note
            </Button>
          </div>
        </form>
      </Form>

      {isError ? (
        <ErrorState message="Failed to load notes." onRetry={() => refetch()} />
      ) : isLoading ? (
        <div className="flex items-center justify-center rounded-lg border p-6">
          <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
        </div>
      ) : (data?.items.length ?? 0) === 0 ? (
        <EmptyState title="No notes yet" />
      ) : (
        <ul className="space-y-3">
          {data?.items.map((note) => (
            <li key={note.id} className="rounded-lg border p-3 text-sm">
              <p className="whitespace-pre-wrap">{note.attributes.note}</p>
              <p className="mt-2 text-xs text-muted-foreground">
                {new Date(note.attributes.created_at).toLocaleString()}
              </p>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
