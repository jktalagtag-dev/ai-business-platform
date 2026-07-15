import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useEmployees } from '@/modules/employee/hooks/useEmployees';
import { NONE_OPTION } from '@/modules/employee/forms/schemas';

/**
 * Employee picker (used for "manager" and ticket technician). Unlike the
 * Department/Position pickers, the Employees list endpoint supports
 * `per_page` up to 100 and `sort`, so this fetches up to 100 sorted by last
 * name — enough for most tenants, but not a live-search combobox (this API
 * has no typeahead-friendly debounced search UI built yet, just a `search`
 * query param).
 *
 * `departmentId` is optional and scopes the options to that department —
 * used for ticket assignment (only a technician in the ticket's own
 * department should be selectable) but deliberately left unset for the
 * Department/Employee "manager" pickers, since a manager legitimately can
 * come from outside the unit.
 */
export function EmployeeSelect({
  value,
  onChange,
  excludeId,
  departmentId,
  placeholder = 'None',
}: {
  value: string;
  onChange: (value: string) => void;
  excludeId?: string;
  departmentId?: string;
  placeholder?: string;
}) {
  const { data, isLoading } = useEmployees({
    per_page: 100,
    sort: 'last_name',
    direction: 'asc',
    department_id: departmentId,
  });
  const options = (data?.items ?? []).filter((e) => e.id !== excludeId);

  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value={NONE_OPTION}>{placeholder}</SelectItem>
        {isLoading && (
          <SelectItem value="__loading__" disabled>
            Loading…
          </SelectItem>
        )}
        {options.map((employee) => (
          <SelectItem key={employee.id} value={employee.id}>
            {employee.attributes.full_name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
