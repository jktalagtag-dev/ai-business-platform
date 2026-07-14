import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { toast } from '@/components/ui/sonner';
import { applyApiErrorsToForm } from '@/lib/apply-api-errors';
import { isApiError } from '@/lib/errors';
import { useAdjustStock } from '@/modules/inventory/hooks/useStock';
import { adjustStockSchema, type AdjustStockFormValues } from '@/modules/inventory/forms/schemas';
import type { InventoryItemResource } from '@/modules/inventory/types';

const HELP_TEXT: Record<AdjustStockFormValues['movement_type'], string> = {
  inbound: 'Enter a positive quantity to add to stock on hand.',
  outbound: 'Enter a negative quantity to remove from stock on hand.',
  adjustment: 'Enter a signed quantity (positive or negative) for a manual correction.',
};

export function AdjustStockDialog({
  open,
  onOpenChange,
  item,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  item?: InventoryItemResource;
}) {
  const adjust = useAdjustStock(item?.attributes.product_id ?? '');

  const form = useForm<AdjustStockFormValues>({
    resolver: zodResolver(adjustStockSchema),
    defaultValues: { movement_type: 'inbound', quantity: undefined, reason: '' },
  });

  useEffect(() => {
    if (open) form.reset({ movement_type: 'inbound', quantity: undefined, reason: '' });
  }, [open, item, form]);

  const movementType = form.watch('movement_type');

  const onSubmit = form.handleSubmit((values) => {
    adjust.mutate(
      { quantity: values.quantity, movement_type: values.movement_type, reason: values.reason || null },
      {
        onSuccess: () => {
          toast.success('Stock adjusted.');
          onOpenChange(false);
        },
        onError: (error) => {
          if (!applyApiErrorsToForm(error, form.setError)) {
            toast.error(isApiError(error) ? error.message : 'Unable to adjust stock.');
          }
        },
      }
    );
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Adjust stock</DialogTitle>
          {item && (
            <DialogDescription>
              {item.attributes.product_name} ({item.attributes.product_sku}) — currently{' '}
              {item.attributes.quantity_on_hand} on hand.
            </DialogDescription>
          )}
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4" noValidate>
            <FormField
              control={form.control}
              name="movement_type"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Movement type</FormLabel>
                  <FormControl>
                    <Select value={field.value} onValueChange={field.onChange}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="inbound">Inbound (stock in)</SelectItem>
                        <SelectItem value="outbound">Outbound (stock out)</SelectItem>
                        <SelectItem value="adjustment">Adjustment (correction)</SelectItem>
                      </SelectContent>
                    </Select>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="quantity"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Quantity</FormLabel>
                  <FormControl>
                    <Input
                      type="number"
                      step="1"
                      value={field.value ?? ''}
                      onChange={(e) => field.onChange(e.target.value)}
                    />
                  </FormControl>
                  <FormDescription>{HELP_TEXT[movementType]}</FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="reason"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Reason (optional)</FormLabel>
                  <FormControl>
                    <Input {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <div className="flex justify-end gap-2">
              <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={adjust.isPending}>
                {adjust.isPending && <Loader2 className="animate-spin" />}
                Adjust
              </Button>
            </div>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
