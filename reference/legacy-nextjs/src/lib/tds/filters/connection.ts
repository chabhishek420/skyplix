/**
 * Connection Type Filter
 * Filter by connection type (cable, dsl, cellular, etc.)
 * Based on Keitaro's ConnectionType filter
 */

import type { RawClick } from '../pipeline/types';
import type { FilterInterface, FilterResult, StreamFilter } from './types';
import { isValidOperator, getOperatorName } from '../data/operators';

/**
 * Connection Type Filter
 */
export class ConnectionTypeFilter implements FilterInterface {
  name = 'connection_type';
  description = 'Filter by connection type (cellular, cable, dsl, etc.)';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { connectionTypes?: string[] };
    const connectionTypes = (payload.connectionTypes || []).map((c: string) => c.toLowerCase());
    const clickConnectionType = (rawClick.connectionType || '').toLowerCase();

    if (!clickConnectionType) {
      return { passed: false, reason: 'Connection type not resolved' };
    }

    const matched = connectionTypes.includes(clickConnectionType);

    return {
      passed: matched,
      reason: matched
        ? `Connection type ${clickConnectionType} is in allowed list`
        : `Connection type ${clickConnectionType} is not in allowed list`,
      matchedValue: matched ? clickConnectionType : undefined
    };
  }
}

/**
 * ISP Filter
 * Filter by Internet Service Provider
 */
export class IspFilter implements FilterInterface {
  name = 'isp';
  description = 'Filter by Internet Service Provider';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { isps?: string[]; matchType?: string };
    const isps = payload.isps || [];
    const matchType = payload.matchType || 'contains';
    const clickIsp = rawClick.isp || '';

    if (!clickIsp) {
      return { passed: false, reason: 'ISP not resolved' };
    }

    let matched = false;
    const lowerIsp = clickIsp.toLowerCase();

    switch (matchType) {
      case 'exact':
        matched = isps.some(i => i.toLowerCase() === lowerIsp);
        break;
      case 'regex':
        try {
          matched = isps.some(i => new RegExp(i, 'i').test(clickIsp));
        } catch {
          matched = false;
        }
        break;
      case 'contains':
      default:
        matched = isps.some(i => lowerIsp.includes(i.toLowerCase()));
    }

    return {
      passed: matched,
      reason: matched
        ? `ISP matches filter`
        : `ISP does not match filter`,
      matchedValue: matched ? clickIsp : undefined
    };
  }
}

/**
 * Operator Filter
 * Filter by mobile operator using OPERATORS dictionary for validation
 */
export class OperatorFilter implements FilterInterface {
  name = 'operator';
  description = 'Filter by mobile operator';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { operators?: string[]; matchType?: string };
    const operators = payload.operators || [];
    const matchType = payload.matchType || 'contains';
    const clickOperator = rawClick.operator || '';

    if (!clickOperator) {
      return { passed: false, reason: 'Operator not resolved' };
    }

    let matched = false;
    const lowerOperator = clickOperator.toLowerCase();

    switch (matchType) {
      case 'exact':
        // Check if operator key is valid against OPERATORS dictionary
        if (isValidOperator(lowerOperator)) {
          matched = operators.some(o => o.toLowerCase() === lowerOperator);
        } else {
          matched = operators.some(o => o.toLowerCase() === lowerOperator);
        }
        break;
      case 'key':
        // Match by operator key (e.g., 'mts_ru', 'beeline_ru')
        matched = operators.some(o => {
          const key = o.toLowerCase();
          if (isValidOperator(key)) {
            return key === lowerOperator;
          }
          return false;
        });
        break;
      case 'regex':
        try {
          matched = operators.some(o => new RegExp(o, 'i').test(clickOperator));
        } catch {
          matched = false;
        }
        break;
      case 'contains':
      default:
        matched = operators.some(o => lowerOperator.includes(o.toLowerCase()));
    }

    // Get operator name for better logging
    const operatorName = isValidOperator(lowerOperator) 
      ? getOperatorName(lowerOperator) 
      : clickOperator;

    return {
      passed: matched,
      reason: matched
        ? `Operator ${operatorName} matches filter`
        : `Operator ${operatorName} does not match filter`,
      matchedValue: matched ? clickOperator : undefined
    };
  }
}
