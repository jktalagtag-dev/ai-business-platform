import { flexRender, getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { ChevronDown, ChevronLeft, ChevronRight, ChevronUp, ChevronsUpDown, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState } from '@/components/layout/EmptyState';
import { ErrorState } from '@/components/layout/ErrorState';
import type { ReactNode } from 'react';

export interface DataTableCursorPagination {
  hasNext: boolean;
  hasPrev: boolean;
  onNext: () => void;
  onPrev: () => void;
}

/**
 * Server-driven sort — most list endpoints in this API have no `sort` param
 * at all, so this is opt-in per table (only Employees supports it today).
 */
export interface DataTableSorting {
  sortBy: string;
  direction: 'asc' | 'desc';
  /** Column ids the server can actually sort by; other columns render unsortable. */
  sortableColumns: string[];
  onSortChange: (columnId: string) => void;
}

/**
 * Generic table driven by a page of already-fetched rows. Every Inventory
 * list hook returns `{ items, pagination }` from the cursor-paginated API, so
 * this component only renders — it never sorts or filters client-side, since
 * most endpoints in this API have no generic `sort` param and a server-fixed
 * per_page. Pass `sorting` for the rare endpoint (Employees) that does.
 */
export function DataTable<TData>({
  columns,
  data,
  isLoading,
  isError,
  onRetry,
  emptyTitle = 'Nothing here yet',
  emptyDescription,
  pagination,
  sorting,
  toolbar,
  onRowClick,
}: {
  columns: ColumnDef<TData, unknown>[];
  data: TData[];
  isLoading?: boolean;
  isError?: boolean;
  onRetry?: () => void;
  emptyTitle?: string;
  emptyDescription?: string;
  pagination?: DataTableCursorPagination;
  sorting?: DataTableSorting;
  toolbar?: ReactNode;
  onRowClick?: (row: TData) => void;
}) {
  const table = useReactTable({ data, columns, getCoreRowModel: getCoreRowModel() });

  return (
    <div className="space-y-4">
      {toolbar && <div className="flex flex-col gap-2 sm:flex-row sm:items-center">{toolbar}</div>}

      {isError ? (
        <ErrorState message="Failed to load data." onRetry={onRetry} />
      ) : isLoading ? (
        <div className="flex items-center justify-center rounded-lg border p-12">
          <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
        </div>
      ) : data.length === 0 ? (
        <EmptyState title={emptyTitle} description={emptyDescription} />
      ) : (
        <div className="rounded-lg border">
          <Table>
            <TableHeader>
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => {
                    const isSortable = sorting?.sortableColumns.includes(header.column.id);
                    const content = header.isPlaceholder
                      ? null
                      : flexRender(header.column.columnDef.header, header.getContext());

                    if (!isSortable || !sorting) {
                      return <TableHead key={header.id}>{content}</TableHead>;
                    }

                    const isActive = sorting.sortBy === header.column.id;
                    const Icon = !isActive ? ChevronsUpDown : sorting.direction === 'asc' ? ChevronUp : ChevronDown;

                    return (
                      <TableHead key={header.id}>
                        <button
                          type="button"
                          onClick={() => sorting.onSortChange(header.column.id)}
                          className="inline-flex items-center gap-1 hover:text-foreground"
                        >
                          {content}
                          <Icon className="h-3.5 w-3.5" />
                        </button>
                      </TableHead>
                    );
                  })}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody>
              {table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  onClick={() => onRowClick?.(row.original)}
                  className={onRowClick ? 'cursor-pointer' : undefined}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      {pagination && (data.length > 0 || pagination.hasPrev) && (
        <div className="flex items-center justify-end gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={pagination.onPrev}
            disabled={!pagination.hasPrev}
          >
            <ChevronLeft className="h-4 w-4" />
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={pagination.onNext}
            disabled={!pagination.hasNext}
          >
            Next
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}
    </div>
  );
}
