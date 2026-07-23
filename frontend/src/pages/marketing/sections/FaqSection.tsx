import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

const FAQS: { question: string; answer: string }[] = [
  {
    question: 'Is there a free plan?',
    answer:
      'Yes — the Free plan covers Inventory, Tickets, and HR for up to 5 team members, with no time limit and no card required.',
  },
  {
    question: 'Is my company’s data isolated from other tenants?',
    answer:
      'Yes. Every account is scoped to its own tenant at the database level, and every request is authenticated and permission-checked before touching your data.',
  },
  {
    question: 'Can I get my data back out?',
    answer:
      'Any table in the app — employees, tickets, products, the audit log — has a one-click CSV export, so you’re never locked in.',
  },
  {
    question: 'Does the AI Assistant only make things up, or can it see my real data?',
    answer:
      'It can call tools that look up your actual tickets, inventory, employees, and anything you’ve uploaded to the Knowledge Base — it isn’t limited to general knowledge.',
  },
  {
    question: 'Do I need to install anything?',
    answer: 'No. It’s a web app — sign up and you’re working in your browser immediately.',
  },
];

export function FaqSection() {
  return (
    <section className="mx-auto w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
      <div className="mx-auto max-w-2xl">
        <h2 className="text-h2 text-center font-bold tracking-tight">
          Frequently asked questions
        </h2>

        <Accordion type="single" collapsible className="mt-8 w-full">
          {FAQS.map((faq, i) => (
            <AccordionItem key={faq.question} value={`item-${i}`}>
              <AccordionTrigger>{faq.question}</AccordionTrigger>
              <AccordionContent>{faq.answer}</AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>
    </section>
  );
}
