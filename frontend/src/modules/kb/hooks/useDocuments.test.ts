import { describe, it, expect } from 'vitest';
import { getKbPollInterval } from '@/modules/kb/hooks/useDocuments';
import { makeKbDocumentResource } from '@/tests/fixtures';

describe('getKbPollInterval', () => {
  it('polls when at least one document is still processing', () => {
    const data = {
      items: [
        makeKbDocumentResource({ id: 'a', status: 'ready' }),
        makeKbDocumentResource({ id: 'b', status: 'processing' }),
      ],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 25 },
    };
    expect(getKbPollInterval(data)).toBe(3000);
  });

  it('stops polling once everything has settled to ready/failed', () => {
    const data = {
      items: [
        makeKbDocumentResource({ id: 'a', status: 'ready' }),
        makeKbDocumentResource({ id: 'b', status: 'failed', error_message: 'No extractable text.' }),
      ],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 25 },
    };
    expect(getKbPollInterval(data)).toBe(false);
  });

  it('does not poll when there is no data yet', () => {
    expect(getKbPollInterval(undefined)).toBe(false);
  });

  it('does not poll an empty list', () => {
    const data = { items: [], pagination: { next_cursor: null, prev_cursor: null, per_page: 25 } };
    expect(getKbPollInterval(data)).toBe(false);
  });
});
