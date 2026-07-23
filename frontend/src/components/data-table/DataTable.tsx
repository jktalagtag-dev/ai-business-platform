import { flexRender, getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import {
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  ChevronUp,
  ChevronsUpDown,
  Download,
  Loader2,
} from 'lucide-react';
import { renderToStaticMarkup } from 'react-dom/server';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { EmptyState } from '@/components/layout/EmptyState';
import { ErrorState } from '@/components/layout/ErrorState';
import type { ReactNode } from 'react';

/** RFC 4180 field escaping — wraps in quotes only when the value actually
 * needs it (contains a comma, quote, or newline), doubling embedded quotes. */
function escapeCsvField(value: unknown): string {
  const text = value === null || value === undefined ? '' : String(value);
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

/** Every column in this app defines a custom `cell` render function (badges,
 * formatted dates, avatar + name, computed lookups) rather than a plain
 * `accessorKey`/`accessorFn` — so there's no raw data value to read for a
 * column in general. Rendering the cell (via the same `flexRender` +
 * `cell.getContext()` the table body itself uses) to static markup and
 * reading its text content is the one generic way to get a plain-text
 * value regardless of what a given column's `cell` does internally. */
function cellPlainText(node: ReactNode): string {
  if (node === null || node === undefined) return '';
  if (typeof node === 'string' || typeof node === 'number') return String(node);
  const html = renderToStaticMarkup(node as Parameters<typeof renderToStaticMarkup>[0]);
  const container = document.createElement('div');
  // A space before every tag boundary keeps adjacent inline elements (e.g.
  // an avatar-initial badge immediately followed by a name) from collapsing
  // into one glued-together word once only the text remains; the final
  // whitespace collapse below cleans up the extra spacing this introduces.
  container.innerHTML = html.replace(/</g, ' <');
  return (container.textContent ?? '').replace(/\s+/g, ' ').trim();
}

/**
 * Exports the currently loaded page of rows to CSV, client-side. Only
 * columns with a non-empty string header are included — the one column
 * convention this app uses for "not a real data column" (a row-action
 * menu) sets `header: ''`, so that's what's actually skipped. Since every
 * list in this app is cursor-paginated with a server-fixed page size, this
 * exports the current page only, not the full remote collection.
 */
function exportRowsToCsv<TData>(
  table: ReturnType<typeof useReactTable<TData>>,
  filename: string
): void {
  const exportableColumnIds = new Set(
    table
      .getAllLeafColumns()
      .filter((column) => {
        const header = column.columnDef.header;
        return typeof header === 'string' && header !== '';
      })
      .map((column) => column.id)
  );

  const headerRow = table
    .getAllLeafColumns()
    .filter((column) => exportableColumnIds.has(column.id))
    .map((column) => escapeCsvField(column.columnDef.header));

  const dataRows = table
    .getRowModel()
    .rows.map((row) =>
      row
        .getVisibleCells()
        .filter((cell) => exportableColumnIds.has(cell.column.id))
        .map((cell) =>
          escapeCsvField(cellPlainText(flexRender(cell.column.columnDef.cell, cell.getContext())))
        )
    );

  const csv = [headerRow, ...dataRows].map((fields) => fields.join(',')).join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename.endsWith('.csv') ? filename : `${filename}.csv`;
  link.click();
  URL.revokeObjectURL(url);
}

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
  exportFilename,
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
  /** When set, renders an Export button that downloads the currently
   * loaded rows as `{exportFilename}.csv` (DESIGN_SYSTEM.md's "every table
   * has Export"). Omit to leave the table without one. */
  exportFilename?: string;
}) {
  const table = useReactTable({ data, columns, getCoreRowModel: getCoreRowModel() });

  return (
    <div className="space-y-4">
      {(toolbar || exportFilename) && (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          {toolbar && (
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">{toolbar}</div>
          )}
          {exportFilename && data.length > 0 && (
            <Button
              type="button"
              variant="outline"
              size="sm"
              className="sm:ml-auto"
              onClick={() => exportRowsToCsv(table, exportFilename)}
            >
              <Download className="h-4 w-4" />
              Export
            </Button>
          )}
        </div>
      )}

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
