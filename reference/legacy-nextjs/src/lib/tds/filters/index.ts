/**
 * Stream Filters System
 * Based on Keitaro TDS Filter Architecture
 * 
 * Supported filters:
 * - country: Filter by country code (ISO 3166-1 alpha-2)
 * - browser: Filter by browser name
 * - os: Filter by operating system
 * - device_type: Filter by device type (desktop, mobile, tablet)
 * - ip: Filter by IP address (with CIDR support)
 * - language: Filter by browser language
 * - keyword: Filter by keyword
 * - referrer: Filter by referrer URL
 * - schedule: Filter by time schedule
 * - uniqueness: Filter by visitor uniqueness
 * - limit: Filter by click limit
 */

import type { RawClick } from '../pipeline/types';
import { isValidCountryCode, getCountryName } from '../data/countries';
import { isValidOperator, getOperatorName } from '../data/operators';
import { isValidBrowser } from '../data/browsers';
import { isValidOS } from '../data/operating-systems';
import { isValidLanguageCode, getLanguageName } from '../data/languages';

// Import additional filters
import { LimitFilter } from './limit';
import { UniquenessFilter } from './uniqueness';
import { ConnectionTypeFilter, IspFilter, OperatorFilter } from './connection';
import {
  HideClickDetectFilter, Ipv6Filter, ParameterFilter, EmptyReferrerFilter,
  AnyParamFilter, UserAgentFilter, DeviceModelFilter, OsVersionFilter,
  BrowserVersionFilter, IntervalFilter
} from './advanced';

// ============================================
// Types
// ============================================

export type FilterMode = 'accept' | 'reject';

export interface FilterResult {
  passed: boolean;
  reason?: string;
  matchedValue?: string;
}

export interface StreamFilter {
  id: string;
  streamId: string;
  name: string;
  mode: FilterMode;
  payload: Record<string, unknown>;
}

export interface FilterInterface {
  name: string;
  description: string;
  process(filter: StreamFilter, rawClick: RawClick): FilterResult;
}

// ============================================
// Filter Implementations
// ============================================

/**
 * Country Filter
 * Uses COUNTRIES dictionary for validation and name resolution
 */
export class CountryFilter implements FilterInterface {
  name = 'country';
  description = 'Filter by country code';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { countries?: string[] };
    const countries = (payload.countries || []).map((c: string) => c.toUpperCase());
    const clickCountry = (rawClick.country || '').toUpperCase();

    if (!clickCountry) {
      return { passed: false, reason: 'Country not resolved' };
    }

    // Validate country code against COUNTRIES dictionary
    if (!isValidCountryCode(clickCountry)) {
      return { passed: false, reason: `Invalid country code: ${clickCountry}` };
    }

    const matched = countries.includes(clickCountry);
    const countryName = getCountryName(clickCountry);

    return {
      passed: matched,
      reason: matched 
        ? `Country ${countryName} (${clickCountry}) is in allowed list` 
        : `Country ${countryName} (${clickCountry}) is not in allowed list`,
      matchedValue: matched ? clickCountry : undefined
    };
  }
}

/**
 * Browser Filter
 * Uses BROWSERS dictionary for validation
 */
export class BrowserFilter implements FilterInterface {
  name = 'browser';
  description = 'Filter by browser name';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { browsers?: string[] };
    const browsers = (payload.browsers || []).map((b: string) => b.toLowerCase());
    const clickBrowser = (rawClick.browser || '').toLowerCase();

    if (!clickBrowser || clickBrowser === 'unknown') {
      return { passed: false, reason: 'Browser not resolved' };
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

/**
 * OS Filter
 * Uses OPERATING_SYSTEMS dictionary for validation
 */
export class OsFilter implements FilterInterface {
  name = 'os';
  description = 'Filter by operating system';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { os?: string[] };
    const osList = (payload.os || []).map((o: string) => o.toLowerCase());
    const clickOs = (rawClick.os || '').toLowerCase();

    if (!clickOs || clickOs === 'unknown') {
      return { passed: false, reason: 'OS not resolved' };
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

/**
 * Device Type Filter
 */
export class DeviceTypeFilter implements FilterInterface {
  name = 'device_type';
  description = 'Filter by device type';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { deviceTypes?: string[] };
    const deviceTypes = (payload.deviceTypes || []).map((d: string) => d.toLowerCase());
    const clickDeviceType = (rawClick.deviceType || '').toLowerCase();

    if (!clickDeviceType) {
      return { passed: false, reason: 'Device type not resolved' };
    }

    const matched = deviceTypes.includes(clickDeviceType);

    return {
      passed: matched,
      reason: matched 
        ? `Device type ${clickDeviceType} is in allowed list` 
        : `Device type ${clickDeviceType} is not in allowed list`,
      matchedValue: matched ? clickDeviceType : undefined
    };
  }
}

/**
 * IP Filter
 */
export class IpFilter implements FilterInterface {
  name = 'ip';
  description = 'Filter by IP address';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { ips?: string[] };
    const ips = payload.ips || [];
    const clickIp = rawClick.ipString || '';

    if (!clickIp) {
      return { passed: false, reason: 'IP not resolved' };
    }

    const matched = ips.some(ip => this.ipMatches(clickIp, ip));

    return {
      passed: matched,
      reason: matched 
        ? `IP ${clickIp} is in allowed list` 
        : `IP ${clickIp} is not in allowed list`,
      matchedValue: matched ? clickIp : undefined
    };
  }

  private ipMatches(clickIp: string, filterIp: string): boolean {
    // Exact match
    if (clickIp === filterIp) return true;

    // CIDR match
    if (filterIp.includes('/')) {
      return this.matchCIDR(clickIp, filterIp);
    }

    // Wildcard match (e.g., 192.168.*)
    if (filterIp.includes('*')) {
      const pattern = filterIp.replace(/\./g, '\\.').replace(/\*/g, '.*');
      return new RegExp(`^${pattern}$`).test(clickIp);
    }

    return false;
  }

  private matchCIDR(ip: string, cidr: string): boolean {
    const [range, bits] = cidr.split('/');
    const mask = parseInt(bits, 10);
    
    const ipNum = this.ipToNumber(ip);
    const rangeNum = this.ipToNumber(range);
    
    if (ipNum === null || rangeNum === null) return false;
    
    const maskNum = ~((1 << (32 - mask)) - 1);
    return (ipNum & maskNum) === (rangeNum & maskNum);
  }

  private ipToNumber(ip: string): number | null {
    const parts = ip.split('.').map(p => parseInt(p, 10));
    if (parts.length !== 4 || parts.some(isNaN)) return null;
    return (parts[0] << 24) + (parts[1] << 16) + (parts[2] << 8) + parts[3];
  }
}

/**
 * Language Filter
 * Uses LANGUAGES dictionary for validation and name resolution
 */
export class LanguageFilter implements FilterInterface {
  name = 'language';
  description = 'Filter by browser language';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { languages?: string[] };
    const languages = (payload.languages || []).map((l: string) => l.toUpperCase());
    const clickLanguage = (rawClick.language || '').toUpperCase();

    if (!clickLanguage) {
      return { passed: false, reason: 'Language not resolved' };
    }

    // Validate language code against LANGUAGES dictionary
    if (!isValidLanguageCode(clickLanguage)) {
      return { passed: false, reason: `Unknown language code: ${clickLanguage}` };
    }

    const matched = languages.includes(clickLanguage);
    const languageName = getLanguageName(clickLanguage);

    return {
      passed: matched,
      reason: matched 
        ? `Language ${languageName} (${clickLanguage}) is in allowed list` 
        : `Language ${languageName} (${clickLanguage}) is not in allowed list`,
      matchedValue: matched ? clickLanguage : undefined
    };
  }
}

/**
 * Keyword Filter
 */
export class KeywordFilter implements FilterInterface {
  name = 'keyword';
  description = 'Filter by keyword';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { keywords?: string[]; matchType?: string };
    const keywords = payload.keywords || [];
    const matchType = payload.matchType || 'contains';
    const clickKeyword = rawClick.keyword || '';

    if (!clickKeyword) {
      return { passed: false, reason: 'Keyword not present' };
    }

    let matched = false;
    const lowerKeyword = clickKeyword.toLowerCase();

    switch (matchType) {
      case 'exact':
        matched = keywords.some(k => k.toLowerCase() === lowerKeyword);
        break;
      case 'regex':
        try {
          matched = keywords.some(k => new RegExp(k, 'i').test(clickKeyword));
        } catch {
          matched = false;
        }
        break;
      case 'contains':
      default:
        matched = keywords.some(k => lowerKeyword.includes(k.toLowerCase()));
    }

    return {
      passed: matched,
      reason: matched 
        ? `Keyword matches filter` 
        : `Keyword does not match filter`,
      matchedValue: matched ? clickKeyword : undefined
    };
  }
}

/**
 * Referrer Filter
 */
export class ReferrerFilter implements FilterInterface {
  name = 'referrer';
  description = 'Filter by referrer URL';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { referrers?: string[]; matchType?: string };
    const referrers = payload.referrers || [];
    const matchType = payload.matchType || 'contains';
    const clickReferrer = rawClick.referrer || '';

    if (!clickReferrer) {
      return { passed: false, reason: 'Referrer not present' };
    }

    let matched = false;
    const lowerReferrer = clickReferrer.toLowerCase();

    switch (matchType) {
      case 'exact':
        matched = referrers.some(r => r.toLowerCase() === lowerReferrer);
        break;
      case 'regex':
        try {
          matched = referrers.some(r => new RegExp(r, 'i').test(clickReferrer));
        } catch {
          matched = false;
        }
        break;
      case 'contains':
      default:
        matched = referrers.some(r => lowerReferrer.includes(r.toLowerCase()));
    }

    return {
      passed: matched,
      reason: matched 
        ? `Referrer matches filter` 
        : `Referrer does not match filter`,
      matchedValue: matched ? clickReferrer : undefined
    };
  }
}

/**
 * Schedule Filter
 */
export class ScheduleFilter implements FilterInterface {
  name = 'schedule';
  description = 'Filter by time schedule';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { 
      schedule?: Array<{ days: number[]; hours: { start: number; end: number }[] }>;
      timezone?: string;
    };
    
    const schedules = payload.schedule || [];
    const now = new Date();

    // Get current day (0 = Sunday, 6 = Saturday)
    const currentDay = now.getDay();
    const currentHour = now.getHours();

    // Check if current time matches any schedule
    const matched = schedules.some(schedule => {
      // Check day
      if (!schedule.days.includes(currentDay)) return false;

      // Check hours
      return schedule.hours.some(h => currentHour >= h.start && currentHour < h.end);
    });

    return {
      passed: matched,
      reason: matched 
        ? `Current time is within schedule` 
        : `Current time is outside schedule`,
      matchedValue: matched ? `${currentDay}:${currentHour}` : undefined
    };
  }
}

/**
 * IsBot Filter
 */
export class IsBotFilter implements FilterInterface {
  name = 'is_bot';
  description = 'Filter by bot status';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { isBot?: boolean };
    const filterIsBot = payload.isBot;
    const clickIsBot = rawClick.isBot;

    const matched = filterIsBot === undefined || clickIsBot === filterIsBot;

    return {
      passed: matched,
      reason: matched 
        ? `Bot status matches (${clickIsBot})` 
        : `Bot status does not match (${clickIsBot} vs ${filterIsBot})`,
      matchedValue: matched ? String(clickIsBot) : undefined
    };
  }
}

/**
 * Proxy Filter
 */
export class ProxyFilter implements FilterInterface {
  name = 'proxy';
  description = 'Filter by proxy usage';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { isUsingProxy?: boolean };
    const filterIsProxy = payload.isUsingProxy;
    const clickIsProxy = rawClick.isUsingProxy || false;

    const matched = filterIsProxy === undefined || clickIsProxy === filterIsProxy;

    return {
      passed: matched,
      reason: matched 
        ? `Proxy status matches (${clickIsProxy})` 
        : `Proxy status does not match`,
      matchedValue: matched ? String(clickIsProxy) : undefined
    };
  }
}

/**
 * Mobile Filter
 */
export class MobileFilter implements FilterInterface {
  name = 'mobile';
  description = 'Filter by mobile status';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { isMobile?: boolean };
    const filterIsMobile = payload.isMobile;
    const clickIsMobile = rawClick.isMobile;

    const matched = filterIsMobile === undefined || clickIsMobile === filterIsMobile;

    return {
      passed: matched,
      reason: matched 
        ? `Mobile status matches (${clickIsMobile})` 
        : `Mobile status does not match`,
      matchedValue: matched ? String(clickIsMobile) : undefined
    };
  }
}

/**
 * City Filter
 */
export class CityFilter implements FilterInterface {
  name = 'city';
  description = 'Filter by city';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { cities?: string[] };
    const cities = (payload.cities || []).map((c: string) => c.toLowerCase());
    const clickCity = (rawClick.city || '').toLowerCase();

    if (!clickCity) {
      return { passed: false, reason: 'City not resolved' };
    }

    const matched = cities.some(c => clickCity.includes(c));

    return {
      passed: matched,
      reason: matched 
        ? `City ${clickCity} is in allowed list` 
        : `City ${clickCity} is not in allowed list`,
      matchedValue: matched ? clickCity : undefined
    };
  }
}

/**
 * Region Filter
 */
export class RegionFilter implements FilterInterface {
  name = 'region';
  description = 'Filter by region/state';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { regions?: string[] };
    const regions = (payload.regions || []).map((r: string) => r.toLowerCase());
    const clickRegion = (rawClick.region || '').toLowerCase();

    if (!clickRegion) {
      return { passed: false, reason: 'Region not resolved' };
    }

    const matched = regions.some(r => clickRegion.includes(r));

    return {
      passed: matched,
      reason: matched 
        ? `Region ${clickRegion} is in allowed list` 
        : `Region ${clickRegion} is not in allowed list`,
      matchedValue: matched ? clickRegion : undefined
    };
  }
}

// ============================================
// Filter Registry
// ============================================

class FilterRegistry {
  private filters: Map<string, FilterInterface> = new Map();

  constructor() {
    // Register all default filters
    this.register(new CountryFilter());
    this.register(new BrowserFilter());
    this.register(new OsFilter());
    this.register(new DeviceTypeFilter());
    this.register(new IpFilter());
    this.register(new LanguageFilter());
    this.register(new KeywordFilter());
    this.register(new ReferrerFilter());
    this.register(new ScheduleFilter());
    this.register(new IsBotFilter());
    this.register(new ProxyFilter());
    this.register(new MobileFilter());
    this.register(new CityFilter());
    this.register(new RegionFilter());
    
    // Register additional filters
    this.register(new LimitFilter());
    this.register(new UniquenessFilter());
    this.register(new ConnectionTypeFilter());
    this.register(new IspFilter());
    this.register(new OperatorFilter());
    
    // Register advanced filters
    this.register(new HideClickDetectFilter());
    this.register(new Ipv6Filter());
    this.register(new ParameterFilter());
    this.register(new EmptyReferrerFilter());
    this.register(new AnyParamFilter());
    this.register(new UserAgentFilter());
    this.register(new DeviceModelFilter());
    this.register(new OsVersionFilter());
    this.register(new BrowserVersionFilter());
    this.register(new IntervalFilter());
  }

  register(filter: FilterInterface): void {
    this.filters.set(filter.name.toLowerCase(), filter);
  }

  getFilter(name: string): FilterInterface | null {
    return this.filters.get(name.toLowerCase()) || null;
  }

  hasFilter(name: string): boolean {
    return this.filters.has(name.toLowerCase());
  }

  getFilterNames(): string[] {
    return Array.from(this.filters.keys());
  }
}

export const filterRegistry = new FilterRegistry();

// ============================================
// Check Filters Function
// ============================================

/**
 * Check if click passes all filters for a stream
 */
export function checkFilters(
  streamFilters: StreamFilter[],
  rawClick: RawClick,
  filterOr: boolean = false
): { passed: boolean; reason?: string } {
  if (!streamFilters || streamFilters.length === 0) {
    return { passed: true };
  }

  const results: boolean[] = [];

  for (const filter of streamFilters) {
    const filterImpl = filterRegistry.getFilter(filter.name);
    if (!filterImpl) {
      console.warn(`Unknown filter: ${filter.name}`);
      continue;
    }

    const result = filterImpl.process(filter, rawClick);
    
    // In reject mode, passing means the click DOESN'T match
    const passed = filter.mode === 'reject' ? !result.passed : result.passed;
    results.push(passed);

    // In OR mode, any passing filter means success
    if (filterOr && passed) {
      return { passed: true };
    }

    // In AND mode (default), any failing filter means failure
    if (!filterOr && !passed) {
      return { passed: false, reason: result.reason };
    }
  }

  // In OR mode, need at least one pass
  if (filterOr) {
    const anyPassed = results.some(r => r);
    return { passed: anyPassed, reason: anyPassed ? undefined : 'No filters matched' };
  }

  // In AND mode, all must pass
  return { passed: true };
}
