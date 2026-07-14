import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useEmployees } from '@/modules/employee/hooks/useEmployees';
import { NONE_OPTION } from '@/modules/employee/forms/schemas';

/**
 * Employee picker (used for "manager"). Unlike the Department/Position
 * pickers, the Employees list endpoint supports `per_page` up to 100 and
 * `sort`, so this fetches up to 100 sorted by last name — enough for most
 * tenants, but not a live-search combobox (this API has no typeahead-friendly
 * debounced search UI built yet, just a `search` query param).
 */
export function EmployeeSelect({
  value,
  onChange,
  excludeId,
  placeholder = 'None',
}: {
  value: string;
  onChange: (value: string) => void;
  excludeId?: string;
  placeholder?: string;
}) {
  const { data, isLoading } = useEmployees({ per_page: 100, sort: 'last_name', direction: 'asc' });
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
