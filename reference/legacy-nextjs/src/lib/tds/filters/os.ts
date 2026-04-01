/**
 * OS Filter
 * Filters by operating system
 */

import type { FilterInterface, StreamFilter, FilterResult } from './types';
import type { RawClick } from '../pipeline/types';

export class OsFilter implements FilterInterface {
  name = 'os';
  description = 'Filter by operating system';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { os?: string[] };
    const osList = (payload.os || []).map((o: string) => o.toLowerCase());
    const clickOs = (rawClick.os || '').toLowerCase();

    if (!clickOs || clickOs === 'unknown') {
      return {
        passed: false,
        reason: 'OS not resolved'
      };
    }

    const matched = osList.some(o => clickOs.includes(o));

    return {
      passed: matched,
      reason: matched 
        ? `OS ${clickOs} is in allowed list` 
        : `OS ${clickOs} is not in allowed list`,
      matchedValue: matched ? clickOs : undefined
    };
  }
}
