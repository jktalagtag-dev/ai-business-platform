import { useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PageHeader } from '@/components/layout/PageHeader';
import { useAbility } from '@/hooks/useAbility';
import { DocumentUploadForm } from '@/modules/kb/components/DocumentUploadForm';
import { DocumentsTable } from '@/modules/kb/components/DocumentsTable';
import { AskPanel } from '@/modules/kb/components/AskPanel';

type TabKey = 'documents' | 'ask';

export function KnowledgeBasePage() {
  const canManage = useAbility('knowledge_base.manage');
  const [active, setActive] = useState<TabKey>('ask');

  return (
    <div>
      <PageHeader
        title="Knowledge Base"
        description="Upload documents and get cited answers from them."
      />

      <Tabs value={active} onValueChange={(v) => setActive(v as TabKey)}>
        <TabsList>
          <TabsTrigger value="ask">Ask</TabsTrigger>
          <TabsTrigger value="documents">Documents</TabsTrigger>
        </TabsList>

        <TabsContent value="ask">
          <AskPanel />
        </TabsContent>

        <TabsContent value="documents">
          <div className="space-y-4">
            {canManage && <DocumentUploadForm />}
            <DocumentsTable />
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
}
