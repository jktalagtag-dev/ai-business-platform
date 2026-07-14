import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AdjustStockDialog } from '@/modules/inventory/components/AdjustStockDialog';
import { stockService } from '@/modules/inventory/services/stock';
import { makeInventoryItemResource } from '@/tests/fixtures';

vi.mock('@/modules/inventory/services/stock', () => ({
  stockService: { adjust: vi.fn() },
}));

function renderDialog(onOpenChange = vi.fn()) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } });
  const item = makeInventoryItemResource();
  render(
    <QueryClientProvider client={queryClient}>
      <AdjustStockDialog open onOpenChange={onOpenChange} item={item} />
    </QueryClientProvider>
  );
  return { onOpenChange, item };
}

beforeEach(() => vi.clearAllMocks());

describe('AdjustStockDialog', () => {
  it('blocks a negative quantity for the default inbound movement type', async () => {
    const user = userEvent.setup();
    renderDialog();

    await user.type(screen.getByLabelText('Quantity'), '-5');
    await user.click(screen.getByRole('button', { name: 'Adjust' }));

    expect(
      await screen.findByText('An inbound movement must have a positive quantity')
    ).toBeInTheDocument();
    expect(stockService.adjust).not.toHaveBeenCalled();
  });

  it('submits a positive inbound quantity as-is', async () => {
    vi.mocked(stockService.adjust).mockResolvedValue(makeInventoryItemResource());
    const user = userEvent.setup();
    const { onOpenChange } = renderDialog();

    await user.type(screen.getByLabelText('Quantity'), '5');
    await user.click(screen.getByRole('button', { name: 'Adjust' }));

    await waitFor(() => expect(onOpenChange).toHaveBeenCalledWith(false));
    expect(stockService.adjust).toHaveBeenCalledWith('product_1', {
      quantity: 5,
      movement_type: 'inbound',
      reason: null,
    });
  });
});
