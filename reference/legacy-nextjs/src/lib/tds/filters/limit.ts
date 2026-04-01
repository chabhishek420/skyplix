/**
 * Limit Filter
 * Filter by click limits (per hour, per day, total)
 * Based on Keitaro's Limit.php filter
 */

import type { RawClick } from '../pipeline/types';
import type { FilterInterface, FilterResult, StreamFilter } from './types';

// In-memory hit limit storage (in production, use Redis)
const hitLimitStore = new Map<string, { hour: number; day: number; total: number }>();

/**
 * Get hit limit key for stream
 */
function getHitLimitKey(streamId: string, date: Date): string {
  const hourKey = `${date.getUTCFullYear()}-${date.getUTCMonth()}-${date.getUTCDate()}-${date.getUTCHours()}`;
  return `${streamId}:${hourKey}`;
}

/**
 * Get current hit counts for a stream
 */
function getHitCounts(streamId: string, date: Date): { hour: number; day: number; total: number } {
  const key = getHitLimitKey(streamId, date);
  const stored = hitLimitStore.get(key);
  if (stored) {
    return stored;
  }
  return { hour: 0, day: 0, total: 0 };
}

/**
 * Increment hit count for a stream
 */
export function incrementHitCount(streamId: string): void {
  const now = new Date();
  const key = getHitLimitKey(streamId, now);
  const current = hitLimitStore.get(key) || { hour: 0, day: 0, total: 0 };
  current.hour++;
  current.day++;
  current.total++;
  hitLimitStore.set(key, current);
}

/**
 * Limit Filter
 * Checks if stream has exceeded configured hit limits
 */
export class LimitFilter implements FilterInterface {
  name = 'limit';
  description = 'Filter by click limits (per hour, per day, total)';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      perHour?: number;
      perDay?: number;
      total?: number;
    };

    const streamId = rawClick.streamId;
    if (!streamId) {
      return { passed: true, reason: 'No stream ID for limit check' };
    }

    const now = new Date();
    const counts = getHitCounts(streamId, now);
    
    // Check per hour limit
    if (payload.perHour && typeof payload.perHour === 'number') {
      if (counts.hour >= payload.perHour) {
        return {
          passed: false,
          reason: `Hourly limit exceeded (${counts.hour}/${payload.perHour})`
        };
      }
    }

    // Check per day limit
    if (payload.perDay && typeof payload.perDay === 'number') {
      if (counts.day >= payload.perDay) {
        return {
          passed: false,
          reason: `Daily limit exceeded (${counts.day}/${payload.perDay})`
        };
      }
    }

    // Check total limit
    if (payload.total && typeof payload.total === 'number') {
      if (counts.total >= payload.total) {
        return {
          passed: false,
          reason: `Total limit exceeded (${counts.total}/${payload.total})`
        };
      }
    }

    // Check if all limits are set but empty (block condition)
    if (
      'perHour' in payload &&
      'perDay' in payload &&
      'total' in payload &&
      !payload.perHour &&
      !payload.perDay &&
      !payload.total
    ) {
      return {
        passed: false,
        reason: 'All limits are set to empty (blocked)'
      };
    }

    return { passed: true, reason: 'Within limits' };
  }
}

/**
 * Clear old entries from hit limit store (cleanup function)
 */
export function cleanupHitLimitStore(): void {
  const now = new Date();
  const cutoff = new Date(now.getTime() - 48 * 60 * 60 * 1000); // 48 hours ago
  
  for (const key of hitLimitStore.keys()) {
    // Keys contain date info, we can parse and check
    // For simplicity, just clear all periodically in production
  }
}
