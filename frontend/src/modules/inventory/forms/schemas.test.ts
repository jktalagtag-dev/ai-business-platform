import { describe, it, expect } from 'vitest';
import { adjustStockSchema, categorySchema, productSchema } from '@/modules/inventory/forms/schemas';

describe('categorySchema', () => {
  it('requires a name', () => {
    const result = categorySchema.safeParse({ name: '', parent_category_id: '__none__' });
    expect(result.success).toBe(false);
  });

  it('accepts a valid category', () => {
    const result = categorySchema.safeParse({ name: 'Electronics', parent_category_id: '__none__' });
    expect(result.success).toBe(true);
  });
});

describe('productSchema', () => {
  it('rejects a negative unit price', () => {
    const result = productSchema.safeParse({
      sku: 'SKU-1',
      name: 'Widget',
      category_id: '__none__',
      unit_price: -5,
      cost_price: 1,
      is_active: true,
    });
    expect(result.success).toBe(false);
  });

  it('accepts a valid product', () => {
    const result = productSchema.safeParse({
      sku: 'SKU-1',
      name: 'Widget',
      category_id: '__none__',
      unit_price: 19.99,
      cost_price: 9.5,
      is_active: true,
    });
    expect(result.success).toBe(true);
  });
});

describe('adjustStockSchema', () => {
  it('rejects a zero quantity', () => {
    const result = adjustStockSchema.safeParse({ movement_type: 'adjustment', quantity: 0 });
    expect(result.success).toBe(false);
  });

  it('rejects a negative quantity for an inbound movement', () => {
    const result = adjustStockSchema.safeParse({ movement_type: 'inbound', quantity: -5 });
    expect(result.success).toBe(false);
  });

  it('rejects a positive quantity for an outbound movement', () => {
    const result = adjustStockSchema.safeParse({ movement_type: 'outbound', quantity: 5 });
    expect(result.success).toBe(false);
  });

  it('accepts a positive inbound and a negative outbound', () => {
    expect(adjustStockSchema.safeParse({ movement_type: 'inbound', quantity: 5 }).success).toBe(true);
    expect(adjustStockSchema.safeParse({ movement_type: 'outbound', quantity: -5 }).success).toBe(true);
  });

  it('accepts either sign for an adjustment', () => {
    expect(adjustStockSchema.safeParse({ movement_type: 'adjustment', quantity: 5 }).success).toBe(true);
    expect(adjustStockSchema.safeParse({ movement_type: 'adjustment', quantity: -5 }).success).toBe(true);
  });
});
