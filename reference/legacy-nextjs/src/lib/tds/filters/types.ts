/**
 * Filter Types
 */

import type { RawClick } from '../pipeline/types';

/**
 * Filter mode
 */
export type FilterMode = 'accept' | 'reject';

/**
 * Filter result
 */
export interface FilterResult {
  passed: boolean;
  reason?: string;
  matchedValue?: string;
}

/**
 * Stream filter configuration (from database)
 */
export interface StreamFilter {
  id: string;
  streamId: string;
  name: string;
  mode: FilterMode;
  payload: Record<string, unknown>;
}

/**
 * Filter interface
 */
export interface FilterInterface {
  name: string;
  description: string;
  process(filter: StreamFilter, rawClick: RawClick): FilterResult;
}
