import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCategories } from '@/modules/inventory/hooks/useCategories';
import { NONE_CATEGORY } from '@/modules/inventory/forms/schemas';

/**
 * Category picker shared by the Category (parent) and Product forms. Only
 * fetches the first page (the categories endpoint has no search/filter and a
 * fixed per_page=25, so very large category trees won't all be selectable
 * here — acceptable for this slice).
 */
export function CategorySelect({
  value,
  onChange,
  excludeId,
  placeholder = 'None',
}: {
  value: string;
  onChange: (value: string) => void;
  /** Exclude this category id (a category cannot be its own parent). */
  excludeId?: string;
  placeholder?: string;
}) {
  const { data, isLoading } = useCategories();
  const options = (data?.items ?? []).filter((c) => c.id !== excludeId);

  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value={NONE_CATEGORY}>{placeholder}</SelectItem>
        {isLoading && (
          <SelectItem value="__loading__" disabled>
            Loading…
          </SelectItem>
        )}
        {options.map((category) => (
          <SelectItem key={category.id} value={category.id}>
            {category.attributes.name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
