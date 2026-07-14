import { z } from 'zod';

/**
 * Zod schemas mirroring the backend Form Requests for Inventory (client-side
 * UX guardrail only — the server re-validates and `error.details[]` is mapped
 * back onto these same fields via applyApiErrorsToForm).
 */

const NONE_CATEGORY = '__none__';

export const categorySchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  // The select uses a sentinel for "no parent" since Radix/native <select>
  // values can't be null; converted back to null before the request is sent.
  parent_category_id: z.string().default(NONE_CATEGORY),
});
export type CategoryFormValues = z.infer<typeof categorySchema>;
export { NONE_CATEGORY };

export const productSchema = z.object({
  sku: z.string().min(1, 'SKU is required').max(100),
  name: z.string().min(1, 'Name is required').max(255),
  description: z.string().max(2000).optional().or(z.literal('')),
  category_id: z.string().default(NONE_CATEGORY),
  unit_price: z.coerce.number({ invalid_type_error: 'Enter a price' }).min(0, 'Must be 0 or more'),
  cost_price: z.coerce.number({ invalid_type_error: 'Enter a price' }).min(0, 'Must be 0 or more'),
  is_active: z.boolean().default(true),
});
export type ProductFormValues = z.infer<typeof productSchema>;

export const supplierSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  contact_email: z
    .string()
    .max(255)
    .email('Enter a valid email address')
    .optional()
    .or(z.literal('')),
  contact_phone: z.string().max(50).optional().or(z.literal('')),
  status: z.enum(['active', 'inactive']).default('active'),
  address_line1: z.string().max(255).optional().or(z.literal('')),
  address_city: z.string().max(255).optional().or(z.literal('')),
  address_state: z.string().max(255).optional().or(z.literal('')),
  address_postal_code: z.string().max(50).optional().or(z.literal('')),
  address_country: z.string().max(255).optional().or(z.literal('')),
});
export type SupplierFormValues = z.infer<typeof supplierSchema>;

export const adjustStockSchema = z
  .object({
    movement_type: z.enum(['inbound', 'outbound', 'adjustment']),
    quantity: z.coerce
      .number({ invalid_type_error: 'Enter a quantity' })
      .int('Must be a whole number')
      .refine((v) => v !== 0, 'Quantity cannot be zero'),
    reason: z.string().max(255).optional().or(z.literal('')),
  })
  .refine((data) => data.movement_type !== 'inbound' || data.quantity > 0, {
    message: 'An inbound movement must have a positive quantity',
    path: ['quantity'],
  })
  .refine((data) => data.movement_type !== 'outbound' || data.quantity < 0, {
    message: 'An outbound movement must have a negative quantity',
    path: ['quantity'],
  });
export type AdjustStockFormValues = z.infer<typeof adjustStockSchema>;
