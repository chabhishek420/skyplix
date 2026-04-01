/**
 * Additional Stream Filters
 * HideClickDetect, Ipv6, Parameter, EmptyReferrer, AnyParam, UserAgent, 
 * DeviceModel, OsVersion, BrowserVersion, Interval
 */

import type { RawClick } from '../pipeline/types';
import type { FilterInterface, FilterResult, StreamFilter } from './types';

/**
 * Hide Click Detect Filter
 * Detects hidden/stealth clicks (iframe with 0 size, opacity 0, etc.)
 */
export class HideClickDetectFilter implements FilterInterface {
  name = 'hide_click_detect';
  description = 'Detect hidden/stolen clicks';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { detect?: boolean };
    const detect = payload.detect !== false;

    if (!detect) {
      return { passed: true, reason: 'Detection disabled' };
    }

    // Check for indicators of hidden clicks
    // This would typically check:
    // - X-Requested-With header (AJAX requests)
    // - iframe embed detection
    // - Screen dimensions
    // - Visibility state

    // For now, check if click came from AJAX without proper referrer
    const isAjax = rawClick.xRequestedWith === 'XMLHttpRequest';
    const hasReferrer = !!rawClick.referrer;

    if (isAjax && !hasReferrer) {
      return {
        passed: false,
        reason: 'Potential hidden click detected (AJAX without referrer)'
      };
    }

    return { passed: true, reason: 'No hidden click indicators detected' };
  }
}

/**
 * IPv6 Filter
 * Filter by IPv6 address support
 */
export class Ipv6Filter implements FilterInterface {
  name = 'ipv6';
  description = 'Filter by IPv6 address';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { allow?: boolean; reject?: boolean };
    const ip = rawClick.ipString || rawClick.ip;

    if (!ip) {
      return { passed: false, reason: 'IP not resolved' };
    }

    const isIpv6 = this.isIpv6(ip);

    // Allow only IPv6
    if (payload.allow && !isIpv6) {
      return {
        passed: false,
        reason: 'IPv6 required but visitor has IPv4'
      };
    }

    // Reject IPv6
    if (payload.reject && isIpv6) {
      return {
        passed: false,
        reason: 'IPv6 addresses are rejected'
      };
    }

    return { passed: true, reason: isIpv6 ? 'IPv6 address' : 'IPv4 address' };
  }

  private isIpv6(ip: string): boolean {
    return ip.includes(':');
  }
}

/**
 * Parameter Filter
 * Filter by custom URL parameter values
 */
export class ParameterFilter implements FilterInterface {
  name = 'parameter';
  description = 'Filter by URL parameter value';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      paramName: string;
      values?: string[];
      matchType?: 'exact' | 'contains' | 'regex' | 'exists';
    };

    if (!payload.paramName) {
      return { passed: true, reason: 'No parameter name specified' };
    }

    // Get parameter value from rawClick extras or source params
    const paramValue = this.getParamValue(rawClick, payload.paramName);

    switch (payload.matchType) {
      case 'exists':
        return {
          passed: paramValue !== null,
          reason: paramValue !== null
            ? `Parameter ${payload.paramName} exists`
            : `Parameter ${payload.paramName} does not exist`
        };

      case 'exact':
        if (!paramValue) {
          return { passed: false, reason: `Parameter ${payload.paramName} not found` };
        }
        const exactMatch = (payload.values || []).includes(paramValue);
        return {
          passed: exactMatch,
          reason: exactMatch
            ? `Parameter ${payload.paramName} matches`
            : `Parameter ${payload.paramName} value "${paramValue}" not in allowed list`
        };

      case 'regex':
        if (!paramValue) {
          return { passed: false, reason: `Parameter ${payload.paramName} not found` };
        }
        try {
          const regexMatch = (payload.values || []).some(pattern =>
            new RegExp(pattern, 'i').test(paramValue)
          );
          return {
            passed: regexMatch,
            reason: regexMatch
              ? `Parameter ${payload.paramName} matches regex`
              : `Parameter ${payload.paramName} does not match any pattern`
          };
        } catch {
          return { passed: false, reason: 'Invalid regex pattern' };
        }

      case 'contains':
      default:
        if (!paramValue) {
          return { passed: false, reason: `Parameter ${payload.paramName} not found` };
        }
        const containsMatch = (payload.values || []).some(value =>
          paramValue.toLowerCase().includes(value.toLowerCase())
        );
        return {
          passed: containsMatch,
          reason: containsMatch
            ? `Parameter ${payload.paramName} contains matching value`
            : `Parameter ${payload.paramName} does not contain any matching value`
        };
    }
  }

  private getParamValue(rawClick: RawClick, paramName: string): string | null {
    // Check common parameter sources
    const paramLower = paramName.toLowerCase();
    
    switch (paramLower) {
      case 'sub1':
        return rawClick.subId1;
      case 'sub2':
        return rawClick.subId2;
      case 'sub3':
        return rawClick.subId3;
      case 'sub4':
        return rawClick.subId4;
      case 'sub5':
        return rawClick.subId5;
      case 'keyword':
      case 'kw':
        return rawClick.keyword;
      case 'source':
        return rawClick.source;
      case 'referrer':
      case 'referer':
        return rawClick.referrer;
      default:
        // Check extra params
        return rawClick.extraParam1 || rawClick.extraParam2 || rawClick.extraParam3;
    }
  }
}

/**
 * Empty Referrer Filter
 * Filter visitors with empty referrer
 */
export class EmptyReferrerFilter implements FilterInterface {
  name = 'empty_referrer';
  description = 'Filter visitors with empty referrer';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { reject?: boolean };
    const hasReferrer = !!rawClick.referrer;

    // Reject visitors with empty referrer
    if (payload.reject && !hasReferrer) {
      return {
        passed: false,
        reason: 'Empty referrer rejected'
      };
    }

    // By default, reject visitors without referrer (direct traffic)
    if (!payload.reject && !hasReferrer) {
      return {
        passed: false,
        reason: 'Empty referrer (direct traffic)'
      };
    }

    return { passed: true, reason: hasReferrer ? 'Has referrer' : 'No referrer check' };
  }
}

/**
 * Any Param Filter
 * Check if any URL parameter matches
 */
export class AnyParamFilter implements FilterInterface {
  name = 'any_param';
  description = 'Filter by any URL parameter';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      params?: string[];
      values?: string[];
      matchType?: 'exact' | 'contains' | 'regex';
    };

    const params = payload.params || [];
    const values = payload.values || [];
    const matchType = payload.matchType || 'contains';

    // Get all param values from rawClick
    const allParamValues = [
      rawClick.subId1,
      rawClick.subId2,
      rawClick.subId3,
      rawClick.subId4,
      rawClick.subId5,
      rawClick.keyword,
      rawClick.source,
      rawClick.extraParam1,
      rawClick.extraParam2,
      rawClick.extraParam3
    ].filter(Boolean) as string[];

    // Check if any value matches
    const matched = allParamValues.some(paramValue => {
      return values.some(value => {
        switch (matchType) {
          case 'exact':
            return paramValue.toLowerCase() === value.toLowerCase();
          case 'regex':
            try {
              return new RegExp(value, 'i').test(paramValue);
            } catch {
              return false;
            }
          case 'contains':
          default:
            return paramValue.toLowerCase().includes(value.toLowerCase());
        }
      });
    });

    return {
      passed: matched,
      reason: matched
        ? 'Found matching parameter value'
        : 'No matching parameter values found'
    };
  }
}

/**
 * User Agent Filter
 * Filter by user agent pattern
 */
export class UserAgentFilter implements FilterInterface {
  name = 'user_agent';
  description = 'Filter by user agent pattern';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      patterns?: string[];
      matchType?: 'contains' | 'regex';
    };

    const patterns = payload.patterns || [];
    const userAgent = rawClick.userAgent || '';

    if (!userAgent) {
      return { passed: false, reason: 'User agent not available' };
    }

    const matchType = payload.matchType || 'contains';

    const matched = patterns.some(pattern => {
      if (matchType === 'regex') {
        try {
          return new RegExp(pattern, 'i').test(userAgent);
        } catch {
          return false;
        }
      }
      return userAgent.toLowerCase().includes(pattern.toLowerCase());
    });

    return {
      passed: matched,
      reason: matched
        ? 'User agent matches pattern'
        : 'User agent does not match any pattern',
      matchedValue: matched ? userAgent.substring(0, 50) : undefined
    };
  }
}

/**
 * Device Model Filter
 * Filter by device model
 */
export class DeviceModelFilter implements FilterInterface {
  name = 'device_model';
  description = 'Filter by device model';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      models?: string[];
      matchType?: 'exact' | 'contains';
    };

    const models = (payload.models || []).map(m => m.toLowerCase());
    const deviceModel = (rawClick.deviceModel || '').toLowerCase();

    if (!deviceModel) {
      return { passed: false, reason: 'Device model not resolved' };
    }

    const matchType = payload.matchType || 'contains';
    const matched = models.some(model => {
      if (matchType === 'exact') {
        return deviceModel === model;
      }
      return deviceModel.includes(model);
    });

    return {
      passed: matched,
      reason: matched
        ? `Device model ${deviceModel} matches`
        : `Device model ${deviceModel} not in allowed list`
    };
  }
}

/**
 * OS Version Filter
 * Filter by operating system version
 */
export class OsVersionFilter implements FilterInterface {
  name = 'os_version';
  description = 'Filter by OS version';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      os?: string;
      minVersion?: string;
      maxVersion?: string;
      versions?: string[];
    };

    const osVersion = rawClick.osVersion;
    const osName = rawClick.os;

    if (!osVersion) {
      return { passed: false, reason: 'OS version not resolved' };
    }

    // Check OS name if specified
    if (payload.os && osName?.toLowerCase() !== payload.os.toLowerCase()) {
      return {
        passed: false,
        reason: `OS ${osName} does not match ${payload.os}`
      };
    }

    // Check specific versions
    if (payload.versions && payload.versions.length > 0) {
      const matched = payload.versions.some(v => v === osVersion);
      return {
        passed: matched,
        reason: matched
          ? `OS version ${osVersion} matches`
          : `OS version ${osVersion} not in allowed list`
      };
    }

    // Check version range
    if (payload.minVersion || payload.maxVersion) {
      const versionNum = parseFloat(osVersion);
      
      if (payload.minVersion && versionNum < parseFloat(payload.minVersion)) {
        return {
          passed: false,
          reason: `OS version ${osVersion} below minimum ${payload.minVersion}`
        };
      }

      if (payload.maxVersion && versionNum > parseFloat(payload.maxVersion)) {
        return {
          passed: false,
          reason: `OS version ${osVersion} above maximum ${payload.maxVersion}`
        };
      }
    }

    return { passed: true, reason: 'OS version check passed' };
  }
}

/**
 * Browser Version Filter
 * Filter by browser version
 */
export class BrowserVersionFilter implements FilterInterface {
  name = 'browser_version';
  description = 'Filter by browser version';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      browser?: string;
      minVersion?: string;
      maxVersion?: string;
      versions?: string[];
    };

    const browserVersion = rawClick.browserVersion;
    const browserName = rawClick.browser;

    if (!browserVersion) {
      return { passed: false, reason: 'Browser version not resolved' };
    }

    // Check browser name if specified
    if (payload.browser && browserName?.toLowerCase() !== payload.browser.toLowerCase()) {
      return {
        passed: false,
        reason: `Browser ${browserName} does not match ${payload.browser}`
      };
    }

    // Check specific versions
    if (payload.versions && payload.versions.length > 0) {
      const matched = payload.versions.some(v => v === browserVersion);
      return {
        passed: matched,
        reason: matched
          ? `Browser version ${browserVersion} matches`
          : `Browser version ${browserVersion} not in allowed list`
      };
    }

    // Check version range
    if (payload.minVersion || payload.maxVersion) {
      const versionNum = parseFloat(browserVersion);
      
      if (payload.minVersion && versionNum < parseFloat(payload.minVersion)) {
        return {
          passed: false,
          reason: `Browser version ${browserVersion} below minimum ${payload.minVersion}`
        };
      }

      if (payload.maxVersion && versionNum > parseFloat(payload.maxVersion)) {
        return {
          passed: false,
          reason: `Browser version ${browserVersion} above maximum ${payload.maxVersion}`
        };
      }
    }

    return { passed: true, reason: 'Browser version check passed' };
  }
}

/**
 * Interval Filter
 * Filter by time interval (distribute traffic over time)
 */
export class IntervalFilter implements FilterInterface {
  name = 'interval';
  description = 'Distribute traffic by time interval';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as {
      seconds?: number;
      minutes?: number;
      hours?: number;
      percentage?: number;
    };

    // Calculate interval in milliseconds
    const intervalMs = ((payload.hours || 0) * 3600 +
                        (payload.minutes || 0) * 60 +
                        (payload.seconds || 0)) * 1000;

    if (intervalMs === 0) {
      return { passed: true, reason: 'No interval specified' };
    }

    // Use timestamp to determine if current click should pass
    const timestamp = rawClick.datetime.getTime();
    const intervalSlot = Math.floor(timestamp / intervalMs);
    const percentage = payload.percentage || 100;

    // Create deterministic but seemingly random distribution
    const slotPercentage = (intervalSlot % 100);

    const passed = slotPercentage < percentage;

    return {
      passed,
      reason: passed
        ? `Click within interval quota (${percentage}%)`
        : `Click outside interval quota (${percentage}%)`
    };
  }
}
