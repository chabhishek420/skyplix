/**
 * Country Filter
 * Filters by country using ISO country codes
 */

import type { FilterInterface, StreamFilter, FilterResult } from './types';
import type { RawClick } from '../pipeline/types';

export class CountryFilter implements FilterInterface {
  name = 'country';
  description = 'Filter by country code (ISO 3166-1 alpha-2)';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { countries?: string[] };
    const countries = (payload.countries || []).map((c: string) => c.toUpperCase());
    const clickCountry = (rawClick.country || '').toUpperCase();

    if (!clickCountry) {
      return {
        passed: false,
        reason: 'Country not resolved'
      };
    }

    const matched = countries.includes(clickCountry);

    return {
      passed: matched,
      reason: matched 
        ? `Country ${clickCountry} is in allowed list` 
        : `Country ${clickCountry} is not in allowed list`,
      matchedValue: matched ? clickCountry : undefined
    };
  }
}
