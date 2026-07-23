import { useMemo, useState } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/ui/confirm-dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table/DataTable';
import { PageHeader } from '@/components/layout/PageHeader';
import { toast } from '@/components/ui/sonner';
import { useAbility } from '@/hooks/useAbility';
import { useDeleteSupplier, useSuppliers } from '@/modules/inventory/hooks/useSuppliers';
import { SupplierFormDialog } from '@/modules/inventory/components/SupplierFormDialog';
import type { SupplierResource } from '@/modules/inventory/types';

const ALL_STATUS = '__all__';

export function SuppliersTab() {
  const canManage = useAbility('suppliers.manage');

  const [search, setSearch] = useState('');
  const [status, setStatus] = useState(ALL_STATUS);
  const [cursor, setCursor] = useState<string | undefined>(undefined);

  const filter = {
    search: search || undefined,
    status: status === ALL_STATUS ? undefined : (status as 'active' | 'inactive'),
    cursor,
  };
  const { data, isLoading, isError, refetch } = useSuppliers(filter);

  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<SupplierResource | undefined>(undefined);
  const [deleting, setDeleting] = useState<SupplierResource | undefined>(undefined);
  const deleteSupplier = useDeleteSupplier();

  function resetToFirstPage<T>(setter: (v: T) => void) {
    return (v: T) => {
      setCursor(undefined);
      setter(v);
    };
  }

  const columns = useMemo<ColumnDef<SupplierResource, unknown>[]>(
    () => [
      { id: 'name', header: 'Name', cell: ({ row }) => row.original.attributes.name },
      {
        id: 'email',
        header: 'Email',
        cell: ({ row }) => row.original.attributes.contact_email ?? (
          <span className="text-muted-foreground">—</span>
        ),
      },
      {
        id: 'phone',
        header: 'Phone',
        cell: ({ row }) => row.original.attributes.contact_phone ?? (
          <span className="text-muted-foreground">—</span>
        ),
      },
      {
        id: 'status',
        header: 'Status',
        cell: ({ row }) =>
          row.original.attributes.status === 'active' ? (
            <Badge variant="success">Active</Badge>
          ) : (
            <Badge variant="secondary">Inactive</Badge>
          ),
      },
      ...(canManage
        ? [
            {
              id: 'actions',
              header: '',
              cell: ({ row }: { row: { original: SupplierResource } }) => (
                <div className="flex justify-end gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                      setEditing(row.original);
                      setFormOpen(true);
                    }}
                  >
                    <Pencil className="h-4 w-4" />
                    <span className="sr-only">Edit</span>
                  </Button>
                  <Button variant="ghost" size="icon" onClick={() => setDeleting(row.original)}>
                    <Trash2 className="h-4 w-4" />
                    <span className="sr-only">Delete</span>
                  </Button>
                </div>
              ),
            } satisfies ColumnDef<SupplierResource, unknown>,
          ]
        : []),
    ],
    [canManage]
  );

  return (
    <div>
      <PageHeader
        title="Suppliers"
        description="Vendors you purchase inventory from."
        actions={
          canManage && (
            <Button
              onClick={() => {
                setEditing(undefined);
                setFormOpen(true);
              }}
            >
              <Plus className="h-4 w-4" />
              New supplier
            </Button>
          )
        }
      />

      <DataTable
        columns={columns}
        data={data?.items ?? []}
        isLoading={isLoading}
        isError={isError}
        onRetry={() => refetch()}
        emptyTitle="No suppliers found"
        toolbar={
          <>
            <Input
              placeholder="Search by name…"
              value={search}
              onChange={(e) => resetToFirstPage(setSearch)(e.target.value)}
              className="sm:max-w-xs"
            />
            <Select value={status} onValueChange={resetToFirstPage(setStatus)}>
              <SelectTrigger className="sm:w-40">
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value={ALL_STATUS}>All statuses</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
              </SelectContent>
            </Select>
          </>
        }
        pagination={{
          hasNext: !!data?.pagination.next_cursor,
          hasPrev: !!data?.pagination.prev_cursor,
          onNext: () => setCursor(data?.pagination.next_cursor ?? undefined),
          onPrev: () => setCursor(data?.pagination.prev_cursor ?? undefined),
        }}
      />

      {canManage && (
        <>
          <SupplierFormDialog open={formOpen} onOpenChange={setFormOpen} supplier={editing} />
          <ConfirmDialog
            open={!!deleting}
            onOpenChange={(open) => !open && setDeleting(undefined)}
            title="Delete supplier?"
            description={`"${deleting?.attributes.name}" will be permanently removed.`}
            confirmLabel="Delete"
            isLoading={deleteSupplier.isPending}
            onConfirm={() => {
              if (!deleting) return;
              deleteSupplier.mutate(deleting.id, {
                onSuccess: () => {
                  toast.success('Supplier deleted.');
                  setDeleting(undefined);
                },
                onError: () => toast.error('Unable to delete supplier.'),
              });
            }}
          />
        </>
      )}
    </div>
  );
}
