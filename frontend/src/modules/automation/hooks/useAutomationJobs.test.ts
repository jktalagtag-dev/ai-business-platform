import { describe, it, expect } from 'vitest';
import { getAutomationJobPollInterval } from '@/modules/automation/hooks/useAutomationJobs';
import { makeAutomationJobResource } from '@/tests/fixtures';

describe('getAutomationJobPollInterval', () => {
  it('polls when at least one job is still queued', () => {
    const data = {
      items: [
        makeAutomationJobResource({ id: 'a', status: 'succeeded' }),
        makeAutomationJobResource({ id: 'b', status: 'queued' }),
      ],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 25 },
    };
    expect(getAutomationJobPollInterval(data)).toBe(3000);
  });

  it('polls when at least one job is still running', () => {
    const data = {
      items: [makeAutomationJobResource({ status: 'running' })],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 25 },
    };
    expect(getAutomationJobPollInterval(data)).toBe(3000);
  });

  it('stops polling once everything has settled to succeeded/failed', () => {
    const data = {
      items: [
        makeAutomationJobResource({ id: 'a', status: 'succeeded' }),
        makeAutomationJobResource({ id: 'b', status: 'failed', error: 'Deliberate test failure.' }),
      ],
      pagination: { next_cursor: null, prev_cursor: null, per_page: 25 },
    };
    expect(getAutomationJobPollInterval(data)).toBe(false);
  });

  it('does not poll when there is no data yet', () => {
    expect(getAutomationJobPollInterval(undefined)).toBe(false);
  });
});
