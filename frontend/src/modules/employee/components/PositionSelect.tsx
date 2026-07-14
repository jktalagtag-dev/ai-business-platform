import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { usePositions } from '@/modules/employee/hooks/usePositions';
import { NONE_OPTION } from '@/modules/employee/forms/schemas';

/** Only fetches the first page — Positions has no search and a fixed
 * per_page=25, same limitation as Inventory's CategorySelect. */
export function PositionSelect({
  value,
  onChange,
  placeholder = 'None',
}: {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}) {
  const { data, isLoading } = usePositions();

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
        {(data?.items ?? []).map((position) => (
          <SelectItem key={position.id} value={position.id}>
            {position.attributes.title}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
