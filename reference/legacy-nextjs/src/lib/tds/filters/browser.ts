/**
 * Browser Filter
 * Filters by browser name
 */

import type { FilterInterface, StreamFilter, FilterResult } from './types';
import type { RawClick } from '../pipeline/types';

export class BrowserFilter implements FilterInterface {
  name = 'browser';
  description = 'Filter by browser name';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { browsers?: string[] };
    const browsers = (payload.browsers || []).map((b: string) => b.toLowerCase());
    const clickBrowser = (rawClick.browser || '').toLowerCase();

    if (!clickBrowser || clickBrowser === 'unknown') {
      return {
        passed: false,
        reason: 'Browser not resolved'
      };
    }

    const matched = browsers.some(b => clickBrowser.includes(b));

    return {
      passed: matched,
      reason: matched 
        ? `Browser ${clickBrowser} is in allowed list` 
        : `Browser ${clickBrowser} is not in allowed list`,
      matchedValue: matched ? clickBrowser : undefined
    };
  }
}
