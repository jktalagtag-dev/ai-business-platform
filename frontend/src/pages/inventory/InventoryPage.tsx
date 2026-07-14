import { useMemo, useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAbility } from '@/hooks/useAbility';
import { CategoriesTab } from '@/modules/inventory/components/CategoriesTab';
import { ProductsTab } from '@/modules/inventory/components/ProductsTab';
import { SuppliersTab } from '@/modules/inventory/components/SuppliersTab';
import { StockTab } from '@/modules/inventory/components/StockTab';

type TabKey = 'products' | 'categories' | 'suppliers' | 'stock';

/**
 * One nav entry, tabbed inside — each tab is independently gated by its own
 * ability, so a role with only `inventory.view` still sees Stock even without
 * `products.view` (see FRONTEND scope reconciliation: no warehouses exist).
 */
export function InventoryPage() {
  const can = useAbility();

  const tabs = useMemo(
    () =>
      (
        [
          { key: 'products', label: 'Products', visible: can('products.view') },
          { key: 'categories', label: 'Categories', visible: can('categories.view') },
          { key: 'suppliers', label: 'Suppliers', visible: can('suppliers.view') },
          { key: 'stock', label: 'Stock', visible: can('inventory.view') },
        ] as const
      ).filter((t) => t.visible),
    [can]
  );

  const [active, setActive] = useState<TabKey>(tabs[0]?.key ?? 'products');

  if (tabs.length === 0) return null;

  return (
    <Tabs value={active} onValueChange={(v) => setActive(v as TabKey)}>
      <TabsList>
        {tabs.map((tab) => (
          <TabsTrigger key={tab.key} value={tab.key}>
            {tab.label}
          </TabsTrigger>
        ))}
      </TabsList>

      <TabsContent value="products">
        <ProductsTab />
      </TabsContent>
      <TabsContent value="categories">
        <CategoriesTab />
      </TabsContent>
      <TabsContent value="suppliers">
        <SuppliersTab />
      </TabsContent>
      <TabsContent value="stock">
        <StockTab />
      </TabsContent>
    </Tabs>
  );
}
