import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/cn';

/** A static example turn, styled with the exact bubble classes ChatMessageBubble
 * uses for real conversations — a fabricated Q&A, not a live chat. */
function MockBubble({ role, content }: { role: 'user' | 'assistant'; content: string }) {
  const isUser = role === 'user';
  return (
    <div className={cn('flex', isUser ? 'justify-end' : 'justify-start')}>
      <div
        className={cn(
          'max-w-sm whitespace-pre-wrap rounded-lg px-3 py-2 text-sm',
          isUser ? 'bg-primary text-primary-foreground' : 'bg-muted'
        )}
      >
        {content}
      </div>
    </div>
  );
}

export function AiAssistantSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="grid items-center gap-12 lg:grid-cols-2">
        <Card className="order-2 lg:order-1">
          <CardContent className="space-y-3 p-6">
            <MockBubble role="user" content="How many critical tickets are open right now?" />
            <MockBubble
              role="assistant"
              content="There are 3 critical tickets open, mostly in Finance and IT. The oldest has been open for 6 hours."
            />
          </CardContent>
        </Card>

        <div className="order-1 lg:order-2">
          <h2 className="text-h2 font-bold tracking-tight">An assistant that knows your data</h2>
          <p className="mt-4 text-muted-foreground">
            The AI Assistant isn't a generic chatbot bolted on the side — it can look up your
            actual tickets, inventory, and employees, and search anything you've added to the
            Knowledge Base, right from a docked panel next to whatever you're working on.
          </p>
        </div>
      </div>
    </section>
  );
}
