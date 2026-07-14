import { useEffect } from 'react';
import { useFieldArray, useForm, useWatch, type Control } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { ChevronDown, ChevronUp, Loader2, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useCreateWorkflow } from '@/modules/automation/hooks/useWorkflows';
import { workflowSchema, type WorkflowFormValues } from '@/modules/automation/forms/schemas';
import { buildWorkflowPayload } from '@/modules/automation/forms/buildWorkflowPayload';
import {
  ACTION_NAMES,
  CONDITION_OPERATORS,
  EVENT_TRIGGER_OPTIONS,
  type ActionName,
} from '@/modules/automation/types';

const EVENT_LABEL: Record<(typeof EVENT_TRIGGER_OPTIONS)[number], string> = {
  'ticket.created': 'Ticket created',
  'ticket.assigned': 'Ticket assigned',
  'ticket.status_changed': 'Ticket status changed',
  'employee.created': 'Employee created',
  'employee.updated': 'Employee updated',
  'employee.archived': 'Employee archived',
};

const OPERATOR_LABEL: Record<(typeof CONDITION_OPERATORS)[number], string> = {
  equals: 'Equals',
  not_equals: 'Does not equal',
  contains: 'Contains',
  greater_than: 'Greater than',
  less_than: 'Less than',
};

const ACTION_LABEL: Record<ActionName, string> = {
  send_notification: 'Send notification',
  log_audit_event: 'Log audit event',
};

const emptyConditionStep: WorkflowFormValues['steps'][number] = {
  kind: 'condition',
  field: '',
  operator: undefined,
  value: '',
  action: undefined,
  to: '',
  subject: '',
  message: '',
  audit_action: '',
  subject_type: '',
  subject_id: '',
};
const emptyActionStep: WorkflowFormValues['steps'][number] = {
  kind: 'action',
  field: '',
  operator: undefined,
  value: '',
  action: undefined,
  to: '',
  subject: '',
  message: '',
  audit_action: '',
  subject_type: '',
  subject_id: '',
};

export function CreateWorkflowDialog({
  open,
  onOpenChange,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const create = useCreateWorkflow();

  const form = useForm<WorkflowFormValues>({
    resolver: zodResolver(workflowSchema),
    defaultValues: {
      name: '',
      description: '',
      trigger_kind: 'event',
      trigger_event: undefined,
      trigger_cron: '',
      steps: [emptyActionStep],
    },
  });

  const { fields, append, remove, move } = useFieldArray({ control: form.control, name: 'steps' });
  const triggerKind = useWatch({ control: form.control, name: 'trigger_kind' });

  useEffect(() => {
    if (open) {
      form.reset({
        name: '',
        description: '',
        trigger_kind: 'event',
        trigger_event: undefined,
        trigger_cron: '',
        steps: [emptyActionStep],
      });
    }
  }, [open, form]);

  const onSubmit = form.handleSubmit((values) => {
    create.mutate(buildWorkflowPayload(values), {
      onSuccess: () => {
        toast.success('Workflow created as a draft — activate it to start running.');
        onOpenChange(false);
      },
      onError: (error) => {
        if (!applyApiErrorsToForm(error, form.setError)) {
          toast.error(isApiError(error) ? error.message : 'Unable to create workflow.');
        }
      },
    });
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>New workflow</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-5" noValidate>
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Name</FormLabel>
                  <FormControl>
                    <Input {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description (optional)</FormLabel>
                  <FormControl>
                    <Textarea rows={2} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <fieldset className="space-y-4 rounded-md border p-3">
              <legend className="px-1 text-sm font-medium text-muted-foreground">Trigger</legend>

              <FormField
                control={form.control}
                name="trigger_kind"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>When should this run?</FormLabel>
                    <FormControl>
                      <Select value={field.value} onValueChange={field.onChange}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="event">On an event</SelectItem>
                          <SelectItem value="schedule">On a schedule (cron)</SelectItem>
                        </SelectContent>
                      </Select>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              {triggerKind === 'event' ? (
                <FormField
                  control={form.control}
                  name="trigger_event"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Event</FormLabel>
                      <FormControl>
                        <Select value={field.value ?? ''} onValueChange={field.onChange}>
                          <SelectTrigger>
                            <SelectValue placeholder="Choose an event…" />
                          </SelectTrigger>
                          <SelectContent>
                            {EVENT_TRIGGER_OPTIONS.map((opt) => (
                              <SelectItem key={opt} value={opt}>
                                {EVENT_LABEL[opt]}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              ) : (
                <FormField
                  control={form.control}
                  name="trigger_cron"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Cron expression</FormLabel>
                      <FormControl>
                        <Input placeholder="0 9 * * 1" {...field} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              )}
            </fieldset>

            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Steps</span>
                <div className="flex gap-2">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => append(emptyConditionStep)}
                  >
                    <Plus className="h-4 w-4" />
                    Condition
                  </Button>
                  <Button type="button" variant="outline" size="sm" onClick={() => append(emptyActionStep)}>
                    <Plus className="h-4 w-4" />
                    Action
                  </Button>
                </div>
              </div>

              {form.formState.errors.steps?.root?.message && (
                <p className="text-sm font-medium text-destructive">
                  {form.formState.errors.steps.root.message}
                </p>
              )}
              {typeof form.formState.errors.steps?.message === 'string' && (
                <p className="text-sm font-medium text-destructive">{form.formState.errors.steps.message}</p>
              )}

              {fields.map((field, index) => (
                <WorkflowStepFields
                  key={field.id}
                  control={form.control}
                  index={index}
                  onRemove={() => remove(index)}
                  onMoveUp={index > 0 ? () => move(index, index - 1) : undefined}
                  onMoveDown={index < fields.length - 1 ? () => move(index, index + 1) : undefined}
                />
              ))}
            </div>

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={create.isPending}>
                {create.isPending && <Loader2 className="animate-spin" />}
                Create workflow
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}

function WorkflowStepFields({
  control,
  index,
  onRemove,
  onMoveUp,
  onMoveDown,
}: {
  control: Control<WorkflowFormValues>;
  index: number;
  onRemove: () => void;
  onMoveUp?: () => void;
  onMoveDown?: () => void;
}) {
  const kind = useWatch({ control, name: `steps.${index}.kind` });
  const action = useWatch({ control, name: `steps.${index}.action` });

  return (
    <div className="space-y-3 rounded-md border p-3">
      <div className="flex items-center justify-between">
        <span className="text-xs font-medium uppercase text-muted-foreground">
          Step {index + 2} — {kind === 'condition' ? 'Condition' : 'Action'}
        </span>
        <div className="flex gap-1">
          <Button type="button" variant="ghost" size="icon" disabled={!onMoveUp} onClick={onMoveUp}>
            <ChevronUp className="h-4 w-4" />
            <span className="sr-only">Move up</span>
          </Button>
          <Button type="button" variant="ghost" size="icon" disabled={!onMoveDown} onClick={onMoveDown}>
            <ChevronDown className="h-4 w-4" />
            <span className="sr-only">Move down</span>
          </Button>
          <Button type="button" variant="ghost" size="icon" onClick={onRemove}>
            <Trash2 className="h-4 w-4" />
            <span className="sr-only">Remove step</span>
          </Button>
        </div>
      </div>

      {kind === 'condition' ? (
        <div className="grid grid-cols-3 gap-3">
          <FormField
            control={control}
            name={`steps.${index}.field`}
            render={({ field }) => (
              <FormItem>
                <FormLabel>Field</FormLabel>
                <FormControl>
                  <Input placeholder="ticket.priority" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          <FormField
            control={control}
            name={`steps.${index}.operator`}
            render={({ field }) => (
              <FormItem>
                <FormLabel>Operator</FormLabel>
                <FormControl>
                  <Select value={field.value ?? ''} onValueChange={field.onChange}>
                    <SelectTrigger>
                      <SelectValue placeholder="Choose…" />
                    </SelectTrigger>
                    <SelectContent>
                      {CONDITION_OPERATORS.map((opt) => (
                        <SelectItem key={opt} value={opt}>
                          {OPERATOR_LABEL[opt]}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          <FormField
            control={control}
            name={`steps.${index}.value`}
            render={({ field }) => (
              <FormItem>
                <FormLabel>Value</FormLabel>
                <FormControl>
                  <Input placeholder="critical" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </div>
      ) : (
        <div className="space-y-3">
          <FormField
            control={control}
            name={`steps.${index}.action`}
            render={({ field }) => (
              <FormItem>
                <FormLabel>Action</FormLabel>
                <FormControl>
                  <Select value={field.value ?? ''} onValueChange={field.onChange}>
                    <SelectTrigger>
                      <SelectValue placeholder="Choose an action…" />
                    </SelectTrigger>
                    <SelectContent>
                      {ACTION_NAMES.map((opt) => (
                        <SelectItem key={opt} value={opt}>
                          {ACTION_LABEL[opt]}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          {action === 'send_notification' && (
            <div className="grid grid-cols-2 gap-3">
              <FormField
                control={control}
                name={`steps.${index}.to`}
                render={({ field }) => (
                  <FormItem className="col-span-2">
                    <FormLabel>Recipient email</FormLabel>
                    <FormControl>
                      <Input type="email" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={control}
                name={`steps.${index}.subject`}
                render={({ field }) => (
                  <FormItem className="col-span-2">
                    <FormLabel>Subject</FormLabel>
                    <FormControl>
                      <Input {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={control}
                name={`steps.${index}.message`}
                render={({ field }) => (
                  <FormItem className="col-span-2">
                    <FormLabel>Message</FormLabel>
                    <FormControl>
                      <Textarea rows={2} {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          )}

          {action === 'log_audit_event' && (
            <div className="grid grid-cols-2 gap-3">
              <FormField
                control={control}
                name={`steps.${index}.audit_action`}
                render={({ field }) => (
                  <FormItem className="col-span-2">
                    <FormLabel>Audit action</FormLabel>
                    <FormControl>
                      <Input placeholder="workflow.escalated" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={control}
                name={`steps.${index}.subject_type`}
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Subject type</FormLabel>
                    <FormControl>
                      <Input placeholder="ticket" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={control}
                name={`steps.${index}.subject_id`}
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Subject id</FormLabel>
                    <FormControl>
                      <Input placeholder="{{ticket.id}}" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          )}
        </div>
      )}
    </div>
  );
}
