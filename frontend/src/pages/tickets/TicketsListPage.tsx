import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { useNavigate } from 'react-router-dom';
import { Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { cn } from '@/lib/cn';
import { useDepartments } from '@/modules/employee/hooks/useDepartments';
import { useTickets } from '@/modules/ticket/hooks/useTickets';
import { TicketFormDialog } from '@/modules/ticket/components/TicketFormDialog';
import { TicketPriorityBadge } from '@/modules/ticket/components/TicketPriorityBadge';
import { TicketStatusBadge } from '@/modules/ticket/components/TicketStatusBadge';
import { TicketStatsBar } from '@/modules/ticket/components/TicketStatsBar';
import { paths } from '@/routes/routes.config';
import type { TicketListParams, TicketQuickFilter, TicketResource } from '@/modules/ticket/types';

const ALL_DEPARTMENTS = '__all__';
const ALL_PRIORITIES = '__all__';
const SORTABLE_COLUMNS = ['ticket_number', 'priority', 'status', 'created_at'];

const QUICK_FILTERS: { key: TicketQuickFilter | null; label: string }[] = [
  { key: null, label: 'All' },
  { key: 'my_tickets', label: 'My tickets' },
  { key: 'open', label: 'Open' },
  { key: 'critical', label: 'Critical' },
  { key: 'unassigned', label: 'Unassigned' },
  { key: 'resolved', label: 'Resolved' },
];

export function TicketsListPage() {
  const navigate = useNavigate();
  const { data: departmentsPage } = useDepartments();

  const [search, setSearch] = useState('');
  const [departmentId, setDepartmentId] = useState(ALL_DEPARTMENTS);
  const [priority, setPriority] = useState(ALL_PRIORITIES);
  const [quickFilter, setQuickFilter] = useState<TicketQuickFilter | null>(null);
  const [cursor, setCursor] = useState<string | undefined>(undefined);
  const [sortBy, setSortBy] = useState<TicketListParams['sort']>('created_at');
  const [direction, setDirection] = useState<TicketListParams['direction']>('desc');

  const filter: TicketListParams = {
    search: search || undefined,
    department_id: departmentId === ALL_DEPARTMENTS ? undefined : departmentId,
    priority: priority === ALL_PRIORITIES ? undefined : (priority as TicketListParams['priority']),
    quick_filter: quickFilter ?? undefined,
    sort: sortBy,
    direction,
    cursor,
  };
  const { data, isLoading, isError, refetch } = useTickets(filter);

  const [createOpen, setCreateOpen] = useState(false);

  const departmentNameById = useMemo(() => {
    const map = new Map<string, string>();
    for (const item of departmentsPage?.items ?? []) map.set(item.id, item.attributes.name);
    return map;
  }, [departmentsPage]);

  function resetToFirstPage<T>(setter: (v: T) => void) {
    return (v: T) => {
      setCursor(undefined);
      setter(v);
    };
  }

  function handleSortChange(columnId: string) {
    setCursor(undefined);
    if (sortBy === columnId) {
      setDirection(direction === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(columnId as TicketListParams['sort']);
      setDirection('asc');
    }
  }

  const columns = useMemo<ColumnDef<TicketResource, unknown>[]>(
    () => [
      {
        id: 'ticket_number',
        header: 'Ticket #',
        cell: ({ row }) => row.original.attributes.ticket_number,
      },
      { id: 'subject', header: 'Subject', cell: ({ row }) => row.original.attributes.subject },
      {
        id: 'department',
        header: 'Department',
        cell: ({ row }) => {
          const id = row.original.attributes.department_id;
          if (!id) return <span className="text-muted-foreground">—</span>;
          return departmentNameById.get(id) ?? <span className="text-muted-foreground">—</span>;
        },
      },
      {
        id: 'priority',
        header: 'Priority',
        cell: ({ row }) => <TicketPriorityBadge priority={row.original.attributes.priority} />,
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => <TicketStatusBadge status={row.original.attributes.status} />,
      },
      {
        id: 'created_at',
        header: 'Created',
        cell: ({ row }) => new Date(row.original.attributes.created_at).toLocaleDateString(),
      },
    ],
    [departmentNameById]
  );

  return (
    <div>
      <PageHeader
        title="Tickets"
        description="Track and resolve IT support requests."
        actions={
          <Button onClick={() => setCreateOpen(true)}>
            <Plus className="h-4 w-4" />
            New ticket
          </Button>
        }
      />

      <TicketStatsBar />

      <div className="mb-4 flex flex-wrap gap-2">
        {QUICK_FILTERS.map((qf) => (
          <button
            key={qf.label}
            type="button"
            onClick={() => resetToFirstPage(setQuickFilter)(qf.key)}
            className={cn(
              'rounded-full border px-3 py-1 text-sm transition-colors',
              quickFilter === qf.key
                ? 'border-primary bg-primary text-primary-foreground'
                : 'hover:bg-accent'
            )}
          >
            {qf.label}
          </button>
        ))}
      </div>

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No tickets found"
        onRowClick={(ticket) => navigate(`${paths.tickets}/${ticket.id}`)}
        exportFilename="tickets"
        toolbar={
          <>
            <Input
              placeholder="Search by subject, description, or ticket #…"
              value={search}
              onChange={(e) => resetToFirstPage(setSearch)(e.target.value)}
              className="sm:max-w-xs"
            />
            <Select value={departmentId} onValueChange={resetToFirstPage(setDepartmentId)}>
              <SelectTrigger className="sm:w-48">
                <SelectValue placeholder="All departments" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_DEPARTMENTS}>All departments</SelectItem>
                {(departmentsPage?.items ?? []).map((d) => (
                  <SelectItem key={d.id} value={d.id}>
                    {d.attributes.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={priority} onValueChange={resetToFirstPage(setPriority)}>
              <SelectTrigger className="sm:w-40">
                <SelectValue placeholder="All priorities" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_PRIORITIES}>All priorities</SelectItem>
                <SelectItem value="low">Low</SelectItem>
                <SelectItem value="medium">Medium</SelectItem>
                <SelectItem value="high">High</SelectItem>
                <SelectItem value="critical">Critical</SelectItem>
              </SelectContent>
            </Select>
          </>
        }
        sorting={{
          sortBy: sortBy ?? 'created_at',
          direction: direction ?? 'desc',
          sortableColumns: SORTABLE_COLUMNS,
          onSortChange: handleSortChange,
        }}
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      <TicketFormDialog open={createOpen} onOpenChange={setCreateOpen} />
    </div>
  );
}
