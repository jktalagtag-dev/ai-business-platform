import { useMemo, useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAbility } from '@/hooks/useAbility';
import { DepartmentsTab } from '@/modules/employee/components/DepartmentsTab';
import { PositionsTab } from '@/modules/employee/components/PositionsTab';

type TabKey = 'departments' | 'positions';

/** Departments and Positions share one page (both simple flat CRUD lists
 * with no filters) — Employees gets its own dedicated list+detail routes
 * since it needs filters/search/sort and a richer detail view. */
export function HRPage() {
  const can = useAbility();

  const tabs = useMemo(
    () =>
      (
        [
          { key: 'departments', label: 'Departments', visible: can('departments.view') },
          { key: 'positions', label: 'Positions', visible: can('positions.view') },
        ] as const
      ).filter((t) => t.visible),
    [can]
  );

  const [active, setActive] = useState<TabKey>(tabs[0]?.key ?? 'departments');

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

      <TabsContent value="departments">
        <DepartmentsTab />
      </TabsContent>
      <TabsContent value="positions">
        <PositionsTab />
      </TabsContent>
    </Tabs>
  );
}
