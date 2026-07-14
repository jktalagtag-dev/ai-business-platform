import type { Resource } from '@/types/api';

// --- Categories ---

export interface CategoryAttributes {
  name: string;
  parent_category_id: string | null;
}

export type CategoryResource = Resource<'category', CategoryAttributes>;

export interface CategoryPayload {
  name: string;
  parent_category_id?: string | null;
}

// --- Products ---
// Note: products have no supplier link in this backend — category is the
// only relationship exposed.

export interface ProductAttributes {
  sku: string;
  name: string;
  description: string | null;
  category_id: string | null;
  /** Decimal, returned as a string by the API. */
  unit_price: string;
  /** Decimal, returned as a string by the API. */
  cost_price: string;
  is_active: boolean;
}

export type ProductResource = Resource<'product', ProductAttributes>;

export interface ProductPayload {
  sku: string;
  name: string;
  description?: string | null;
  category_id?: string | null;
  unit_price: number;
  cost_price: number;
  is_active?: boolean;
}

export interface ProductListParams {
  category_id?: string;
  is_active?: boolean;
  search?: string;
  cursor?: string;
}

// --- Suppliers ---

export type SupplierStatus = 'active' | 'inactive';

export interface SupplierAddress {
  line1?: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
}

export interface SupplierAttributes {
  name: string;
  contact_email: string | null;
  contact_phone: string | null;
  address: SupplierAddress | null;
  status: SupplierStatus;
}

export type SupplierResource = Resource<'supplier', SupplierAttributes>;

export interface SupplierPayload {
  name: string;
  contact_email?: string | null;
  contact_phone?: string | null;
  address?: SupplierAddress | null;
  status?: SupplierStatus;
}

export interface SupplierListParams {
  status?: SupplierStatus;
  search?: string;
  cursor?: string;
}

// --- Stock ---
// The API tracks one inventory_item per product (an internal-only "Main
// Warehouse" — there is no warehouse concept in this contract).

export interface InventoryItemAttributes {
  product_id: string;
  product_sku: string;
  product_name: string;
  quantity_on_hand: number;
  quantity_reserved: number;
  reorder_point: number;
  reorder_quantity: number;
  is_low_stock: boolean;
}

export type InventoryItemResource = Resource<'inventory_item', InventoryItemAttributes>;

export interface StockListParams {
  low_stock?: boolean;
  cursor?: string;
}

export type MovementType = 'inbound' | 'outbound' | 'adjustment';

export interface AdjustStockPayload {
  /** Signed delta — positive for inbound, negative for outbound, either sign for adjustment. */
  quantity: number;
  movement_type: MovementType;
  reason?: string | null;
}

export interface InventoryMovementAttributes {
  movement_type: MovementType;
  quantity: number;
  reason: string | null;
  created_by_user_id: string | null;
  created_at: string;
}

export type InventoryMovementResource = Resource<'inventory_movement', InventoryMovementAttributes>;
