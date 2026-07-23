import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { PageHeader } from '@/components/layout/PageHeader';
import { Input } from '@/components/ui/input';
import { DataTable } from '@/components/data-table/DataTable';
import { useAuditLogs } from '@/modules/audit/hooks/useAuditLogs';
import type { AuditLogResource } from '@/modules/audit/types';

function formatDate(value: string): string {
  return new Date(value).toLocaleString();
}

/** `changes` has no fixed shape (it varies per call site server-side), so it's
 * rendered as a raw JSON dump rather than field-specific formatting. */
function ChangesCell({ changes }: { changes: Record<string, unknown> }) {
  if (Object.keys(changes).length === 0) return <span className="text-muted-foreground">—</span>;
  return (
    <details>
      <summary className="cursor-pointer text-sm text-muted-foreground">View</summary>
      <pre className="mt-1 max-w-xs overflow-x-auto rounded bg-muted p-2 text-xs">
        {JSON.stringify(changes, null, 2)}
      </pre>
    </details>
  );
}

export function AuditLogPage() {
  const [subjectType, setSubjectType] = useState('');
  const [subjectId, setSubjectId] = useState('');
  const [cursor, setCursor] = useState<string | undefined>(undefined);

  const { data, isLoading, isError, refetch } = useAuditLogs({
    subject_type: subjectType || undefined,
    subject_id: subjectId || undefined,
    cursor,
  });

  function resetToFirstPage<T>(setter: (v: T) => void) {
    return (v: T) => {
      setCursor(undefined);
      setter(v);
    };
  }

  const columns = useMemo<ColumnDef<AuditLogResource, unknown>[]>(
    () => [
      {
        id: 'actor',
        header: 'Actor',
        cell: ({ row }) => row.original.attributes.actor_user_id ?? 'System',
      },
      { id: 'action', header: 'Action', cell: ({ row }) => row.original.attributes.action },
      {
        id: 'subject',
        header: 'Subject',
        cell: ({ row }) =>
          `${row.original.attributes.subject_type} #${row.original.attributes.subject_id}`,
      },
      {
        id: 'changes',
        header: 'Changes',
        cell: ({ row }) => <ChangesCell changes={row.original.attributes.changes} />,
      },
      {
        id: 'ip_address',
        header: 'IP address',
        cell: ({ row }) => row.original.attributes.ip_address ?? '—',
      },
      {
        id: 'created_at',
        header: 'When',
        cell: ({ row }) => formatDate(row.original.attributes.created_at),
      },
    ],
    []
  );

  return (
    <div className="space-y-4">
      <PageHeader title="Audit Log" description="A record of who changed what, and when." />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No audit log entries yet"
        toolbar={
          <>
            <Input
              placeholder="Subject type (e.g. ticket)"
              value={subjectType}
              onChange={(e) => resetToFirstPage(setSubjectType)(e.target.value)}
              className="sm:w-56"
            />
            <Input
              placeholder="Subject id"
              value={subjectId}
              onChange={(e) => resetToFirstPage(setSubjectId)(e.target.value)}
              className="sm:w-56"
            />
          </>
        }
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />
    </div>
  );
}
