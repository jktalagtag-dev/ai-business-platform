import { PageHeader } from '@/components/layout/PageHeader';
import { AutomationJobsTable } from '@/modules/automation/components/AutomationJobsTable';

export function AutomationJobsListPage() {
  return (
    <div>
      <PageHeader title="Automation Jobs" description="Run history across all workflows." />
      <AutomationJobsTable />
    </div>
  );
}
