import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useDepartments } from '@/modules/employee/hooks/useDepartments';
import { NONE_OPTION } from '@/modules/employee/forms/schemas';

/** Only fetches the first page — Departments has no search and a fixed
 * per_page=25, same limitation as Inventory's CategorySelect. */
export function DepartmentSelect({
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
  const { data, isLoading } = useDepartments();
  const options = (data?.items ?? []).filter((d) => d.id !== excludeId);

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
        {options.map((department) => (
          <SelectItem key={department.id} value={department.id}>
            {department.attributes.name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
