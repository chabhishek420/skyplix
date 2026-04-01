/**
 * Macros Processor
 * 
 * Replaces macros in URLs and content.
 * Based on Keitaro's MacrosProcessor.php
 * 
 * Features:
 * - Standard macros: {macro_name}
 * - Raw mode macros: {_macro_name} (no URL encoding)
 * - Parameter macros: {param_name} reads from query params
 * - Functional macros: {macro:arg1,arg2}
 * - File loading: {from_file:filename}
 * - Sampling: {sample:percent} for A/B testing
 */

import { readFile } from 'fs/promises';
import { join } from 'path';

export interface MacroData {
  clickId: string;
  campaignId: string;
  campaignName?: string;
  streamId: string;
  streamName?: string;
  pubId?: string;
  subId?: string;
  subId1?: string;
  subId2?: string;
  subId3?: string;
  subId4?: string;
  subId5?: string;
  ip?: string;
  country?: string;
  region?: string;
  city?: string;
  browser?: string;
  browserVersion?: string;
  os?: string;
  osVersion?: string;
  device?: string;
  deviceType?: string;
  deviceModel?: string;
  deviceBrand?: string;
  userAgent?: string;
  referrer?: string;
  keyword?: string;
  source?: string;
  trafficSourceName?: string;
  timestamp?: string;
  date?: string;
  offerId?: string;
  offerName?: string;
  landingId?: string;
  landingName?: string;
  affiliateNetworkId?: string;
  creativeId?: string;
  adCampaignId?: string;
  externalId?: string;
  // New macros
  isp?: string;
  operator?: string;
  connectionType?: string;
  parentCampaignId?: string;
  token?: string;
  visitorCode?: string;
  sessionId?: string;
  // Revenue/Cost macros
  revenue?: string;
  cost?: string;
  payout?: string;
  profit?: string;
  // Conversion status
  status?: string;
  originalStatus?: string;
  // Transaction ID
  tid?: string;
  transactionId?: string;
  // Current domain
  currentDomain?: string;
  // Goals
  goal1?: string;
  goal2?: string;
  goal3?: string;
  goal4?: string;
  // Query parameters (for dynamic param macros)
  queryParams?: Record<string, string>;
}

// Cache for file contents
const fileCache: Map<string, { content: string; timestamp: number }> = new Map();
const FILE_CACHE_TTL = 60000; // 1 minute cache

/**
 * Load content from a file
 */
async function loadFromFile(filename: string): Promise<string> {
  try {
    // Check cache first
    const cached = fileCache.get(filename);
    if (cached && Date.now() - cached.timestamp < FILE_CACHE_TTL) {
      return cached.content;
    }

    // Try multiple possible paths
    const possiblePaths = [
      join(process.cwd(), 'data', 'macros', filename),
      join(process.cwd(), 'macros', filename),
      join(process.cwd(), filename),
    ];

    for (const filePath of possiblePaths) {
      try {
        const content = await readFile(filePath, 'utf-8');
        // Cache the result
        fileCache.set(filename, { content: content.trim(), timestamp: Date.now() });
        return content.trim();
      } catch {
        continue;
      }
    }

    return '';
  } catch {
    return '';
  }
}

/**
 * Sample function - returns true percent% of the time based on clickId
 * Uses deterministic sampling based on clickId for consistency
 */
function samplePercent(percent: number, clickId: string): string {
  // Use clickId hash for deterministic sampling
  // Same clickId always returns same result
  let hash = 0;
  for (let i = 0; i < clickId.length; i++) {
    const char = clickId.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32bit integer
  }
  
  // Normalize to 0-100 range
  const normalizedValue = Math.abs(hash % 100);
  
  // Return 'true' if within the percent threshold
  return normalizedValue < percent ? 'true' : 'false';
}

/**
 * Get standard macro value
 */
function getStandardMacroValue(name: string, data: MacroData): string | undefined {
  const macroMap: Record<string, string | undefined> = {
    // Click identifiers
    'clickid': data.clickId,
    'click_id': data.clickId,
    'cid': data.clickId,
    
    // Campaign/Stream
    'campaign_id': data.campaignId,
    'campaignid': data.campaignId,
    'campaign_name': data.campaignName || '',
    'stream_id': data.streamId,
    'streamid': data.streamId,
    'stream_name': data.streamName || '',
    'parent_campaign_id': data.parentCampaignId || '',
    
    // Traffic Source
    'traffic_source_name': data.trafficSourceName || '',
    
    // Publisher
    'pub_id': data.pubId || '',
    'pubid': data.pubId || '',
    'subid': data.subId || '',
    'sub_id': data.subId || '',
    
    // Sub IDs
    'sub_id_1': data.subId1 || '',
    'subid1': data.subId1 || '',
    'sub_id_2': data.subId2 || '',
    'subid2': data.subId2 || '',
    'sub_id_3': data.subId3 || '',
    'subid3': data.subId3 || '',
    'sub_id_4': data.subId4 || '',
    'subid4': data.subId4 || '',
    'sub_id_5': data.subId5 || '',
    'subid5': data.subId5 || '',
    
    // Geo
    'ip': data.ip || '',
    'country': data.country || '',
    'country_code': data.country || '',
    'region': data.region || '',
    'city': data.city || '',
    
    // Device
    'browser': data.browser || '',
    'browser_version': data.browserVersion || '',
    'os': data.os || '',
    'os_version': data.osVersion || '',
    'device': data.device || '',
    'device_type': data.deviceType || data.device || '',
    'device_model': data.deviceModel || '',
    'device_brand': data.deviceBrand || '',
    'user_agent': data.userAgent || '',
    'ua': data.userAgent || '',
    
    // Connection/ISP
    'isp': data.isp || '',
    'operator': data.operator || '',
    'connection_type': data.connectionType || '',
    
    // Traffic
    'referrer': data.referrer || '',
    'referer': data.referrer || '',
    'keyword': data.keyword || '',
    'kw': data.keyword || '',
    'source': data.source || '',
    
    // Current domain
    'current_domain': data.currentDomain || '',
    'domain': data.currentDomain || '',
    
    // Timestamp
    'timestamp': data.timestamp || Math.floor(Date.now() / 1000).toString(),
    'date': data.date || new Date().toISOString().split('T')[0],
    'time': new Date().toTimeString().split(' ')[0],
    'datetime': new Date().toISOString(),
    
    // Offer/Landing
    'offer_id': data.offerId || '',
    'offer_name': data.offerName || '',
    'landing_id': data.landingId || '',
    'landing_name': data.landingName || '',
    'affiliate_network_id': data.affiliateNetworkId || '',
    
    // Additional tracking
    'creative_id': data.creativeId || '',
    'ad_campaign_id': data.adCampaignId || '',
    'external_id': data.externalId || '',
    
    // Session/Visitor
    'visitor_code': data.visitorCode || '',
    'session_id': data.sessionId || '',
    'token': data.token || '',
    
    // Revenue/Cost/Profit
    'revenue': data.revenue || '0',
    'cost': data.cost || '0',
    'payout': data.payout || '0',
    'profit': data.profit || calculateProfit(data.revenue, data.cost),
    
    // Conversion status
    'status': data.status || '',
    'original_status': data.originalStatus || '',
    
    // Transaction ID
    'tid': data.tid || data.transactionId || '',
    'transaction_id': data.transactionId || data.tid || '',
    
    // Goals
    'goal_1': data.goal1 || '0',
    'goal_2': data.goal2 || '0',
    'goal_3': data.goal3 || '0',
    'goal_4': data.goal4 || '0',
    
    // Random
    'random': Math.random().toString(36).substring(2, 10),
    'rand': Math.floor(Math.random() * 1000000).toString(),
  };

  return macroMap[name.toLowerCase()];
}

/**
 * Calculate profit from revenue and cost
 */
function calculateProfit(revenue?: string, cost?: string): string {
  const rev = parseFloat(revenue || '0');
  const c = parseFloat(cost || '0');
  return (rev - c).toFixed(2);
}

/**
 * Get query parameter value (for dynamic param macros)
 */
function getQueryParamValue(paramName: string, data: MacroData): string | undefined {
  // First check if it's in the queryParams object
  if (data.queryParams && data.queryParams[paramName]) {
    return data.queryParams[paramName];
  }
  
  // Then check if it exists as a direct property on data (for subid values, etc.)
  const key = paramName.toLowerCase().replace(/[_-]/g, '');
  const dataAny = data as any;
  
  // Common parameter aliases
  const aliases: Record<string, string[]> = {
    'subid': ['subId', 'sub_id', 'subid'],
    'subid1': ['subId1', 'sub_id_1', 'subid1'],
    'subid2': ['subId2', 'sub_id_2', 'subid2'],
    'subid3': ['subId3', 'sub_id_3', 'subid3'],
    'subid4': ['subId4', 'sub_id_4', 'subid4'],
    'subid5': ['subId5', 'sub_id_5', 'subid5'],
    'clickid': ['clickId', 'click_id', 'cid'],
  };
  
  // Check aliases
  for (const [alias, props] of Object.entries(aliases)) {
    if (key === alias) {
      for (const prop of props) {
        if (dataAny[prop]) {
          return dataAny[prop];
        }
      }
    }
  }
  
  return undefined;
}

/**
 * Replace macros in content (synchronous version - no file loading)
 */
export function replaceMacrosSync(content: string, data: MacroData): string {
  if (!content.includes('{') && !content.includes('$')) {
    return content;
  }

  let result = content;

  // Handle raw mode prefix: {_macro_name} - no URL encoding
  result = result.replace(/\{_([a-z_0-9]+)\}/gi, (match, name) => {
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return value; // Raw value without encoding
    }
    
    // Try query param
    const paramValue = getQueryParamValue(name, data);
    if (paramValue !== undefined) {
      return paramValue;
    }
    
    return match;
  });

  // Handle functional macros with raw mode: {_macro:arg1,arg2}
  result = result.replace(/\{_([a-z_0-9]+):([^}]*)\}/gi, (match, name, args) => {
    // Handle sample macro
    if (name.toLowerCase() === 'sample') {
      const percent = parseFloat(args);
      if (!isNaN(percent) && percent >= 0 && percent <= 100) {
        return samplePercent(percent, data.clickId);
      }
    }
    
    // Handle from_file macro (return placeholder in sync mode)
    if (name.toLowerCase() === 'from_file') {
      return `[file:${args}]`; // Placeholder for async processing
    }
    
    return match;
  });

  // Handle standard macros with URL encoding
  result = result.replace(/\{([a-z_0-9]+)\}/gi, (match, name) => {
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return encodeURIComponent(value);
    }
    
    // Try query param
    const paramValue = getQueryParamValue(name, data);
    if (paramValue !== undefined) {
      return encodeURIComponent(paramValue);
    }
    
    return match;
  });

  // Handle functional macros: {macro:arg1,arg2}
  result = result.replace(/\{([a-z_0-9]+):([^}]*)\}/gi, (match, name, args) => {
    const lowerName = name.toLowerCase();
    
    // Handle sample macro
    if (lowerName === 'sample') {
      const percent = parseFloat(args);
      if (!isNaN(percent) && percent >= 0 && percent <= 100) {
        return samplePercent(percent, data.clickId);
      }
      return match;
    }
    
    // Handle from_file macro (return placeholder in sync mode)
    if (lowerName === 'from_file') {
      return `[file:${args}]`; // Placeholder for async processing
    }
    
    // Try to get standard macro value
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return encodeURIComponent(value);
    }
    
    return match;
  });

  // Handle variable-style macros: $sub_id_1
  result = result.replace(/\$([a-z_0-9]+)/gi, (match, name) => {
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return encodeURIComponent(value);
    }
    
    // Try query param
    const paramValue = getQueryParamValue(name, data);
    if (paramValue !== undefined) {
      return encodeURIComponent(paramValue);
    }
    
    return match;
  });

  return result;
}

/**
 * Replace macros in content (async version - supports file loading)
 */
export async function replaceMacros(content: string, data: MacroData): Promise<string> {
  if (!content.includes('{') && !content.includes('$')) {
    return content;
  }

  let result = content;

  // Handle raw mode prefix: {_macro_name} - no URL encoding
  result = result.replace(/\{_([a-z_0-9]+)\}/gi, (match, name) => {
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return value; // Raw value without encoding
    }
    
    // Try query param
    const paramValue = getQueryParamValue(name, data);
    if (paramValue !== undefined) {
      return paramValue;
    }
    
    return match;
  });

  // Handle functional macros with raw mode: {_macro:arg1,arg2}
  result = result.replace(/\{_([a-z_0-9]+):([^}]*)\}/gi, (match, name, args) => {
    const lowerName = name.toLowerCase();
    
    // Handle sample macro
    if (lowerName === 'sample') {
      const percent = parseFloat(args);
      if (!isNaN(percent) && percent >= 0 && percent <= 100) {
        return samplePercent(percent, data.clickId);
      }
    }
    
    // from_file not supported in raw mode (async operation)
    return match;
  });

  // Handle from_file macro first (async): {from_file:filename}
  const fileMatches = result.match(/\{from_file:([^}]+)\}/gi);
  if (fileMatches) {
    for (const match of fileMatches) {
      const filename = match.replace(/\{from_file:|\}/gi, '');
      const content = await loadFromFile(filename);
      result = result.replace(new RegExp(`\\{from_file:${filename.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\}`, 'gi'), encodeURIComponent(content));
    }
  }

  // Handle standard macros with URL encoding
  result = result.replace(/\{([a-z_0-9]+)\}/gi, (match, name) => {
    // Skip from_file (already processed)
    if (name.toLowerCase() === 'from_file') {
      return match;
    }
    
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return encodeURIComponent(value);
    }
    
    // Try query param
    const paramValue = getQueryParamValue(name, data);
    if (paramValue !== undefined) {
      return encodeURIComponent(paramValue);
    }
    
    return match;
  });

  // Handle functional macros: {macro:arg1,arg2}
  result = result.replace(/\{([a-z_0-9]+):([^}]*)\}/gi, (match, name, args) => {
    const lowerName = name.toLowerCase();
    
    // Skip from_file (already processed)
    if (lowerName === 'from_file') {
      return match;
    }
    
    // Handle sample macro
    if (lowerName === 'sample') {
      const percent = parseFloat(args);
      if (!isNaN(percent) && percent >= 0 && percent <= 100) {
        return samplePercent(percent, data.clickId);
      }
      return match;
    }
    
    // Try to get standard macro value
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return encodeURIComponent(value);
    }
    
    return match;
  });

  // Handle variable-style macros: $sub_id_1
  result = result.replace(/\$([a-z_0-9]+)/gi, (match, name) => {
    const value = getStandardMacroValue(name, data);
    if (value !== undefined) {
      return encodeURIComponent(value);
    }
    
    // Try query param
    const paramValue = getQueryParamValue(name, data);
    if (paramValue !== undefined) {
      return encodeURIComponent(paramValue);
    }
    
    return match;
  });

  return result;
}

/**
 * Generate affiliate URL with macros
 */
export function buildAffiliateUrl(baseUrl: string, data: MacroData): string {
  let url = replaceMacrosSync(baseUrl, data);
  
  // Add standard parameters if not already present
  try {
    const urlObj = new URL(url);
    
    if (!urlObj.searchParams.has('clickid') && !urlObj.searchParams.has('click_id')) {
      urlObj.searchParams.set('clickid', data.clickId);
    }
    
    if (!urlObj.searchParams.has('aff_sub2') && data.clickId) {
      urlObj.searchParams.set('aff_sub2', data.clickId);
    }
    
    if (!urlObj.searchParams.has('aff_sub') && data.pubId) {
      urlObj.searchParams.set('aff_sub', data.pubId);
    }
    
    // Add sub_id if present
    if (!urlObj.searchParams.has('sub_id') && data.subId) {
      urlObj.searchParams.set('sub_id', data.subId);
    }
    
    // Add token if present
    if (!urlObj.searchParams.has('token') && data.token) {
      urlObj.searchParams.set('token', data.token);
    }
    
    // Add tid if present
    if (!urlObj.searchParams.has('tid') && data.tid) {
      urlObj.searchParams.set('tid', data.tid);
    }
    
    url = urlObj.toString();
  } catch {
    // Invalid URL, return as-is
  }
  
  return url;
}

/**
 * Create MacroData from RawClick
 */
export function createMacroDataFromRawClick(rawClick: any, campaign?: any, stream?: any, request?: any): MacroData {
  // Extract query parameters from request if available
  let queryParams: Record<string, string> = {};
  if (request?.url) {
    try {
      const url = new URL(request.url, 'http://localhost');
      url.searchParams.forEach((value, key) => {
        queryParams[key] = value;
      });
    } catch {
      // Invalid URL
    }
  }

  return {
    clickId: rawClick.clickId || '',
    campaignId: campaign?.id || rawClick.campaignId || '',
    campaignName: campaign?.name || rawClick.campaignName || '',
    streamId: stream?.id || rawClick.streamId || '',
    streamName: stream?.name || rawClick.streamName || '',
    pubId: rawClick.pubId || rawClick.subId || '',
    subId: rawClick.subId || '',
    subId1: rawClick.subId1 || '',
    subId2: rawClick.subId2 || '',
    subId3: rawClick.subId3 || '',
    subId4: rawClick.subId4 || '',
    subId5: rawClick.subId5 || '',
    ip: rawClick.ip || '',
    country: rawClick.country || '',
    region: rawClick.region || '',
    city: rawClick.city || '',
    browser: rawClick.browser || '',
    browserVersion: rawClick.browserVersion || '',
    os: rawClick.os || '',
    osVersion: rawClick.osVersion || '',
    device: rawClick.deviceType || '',
    deviceType: rawClick.deviceType || '',
    deviceModel: rawClick.deviceModel || '',
    deviceBrand: rawClick.deviceBrand || '',
    userAgent: rawClick.userAgent || '',
    referrer: rawClick.referrer || '',
    keyword: rawClick.keyword || '',
    source: rawClick.source || '',
    trafficSourceName: rawClick.trafficSourceName || campaign?.trafficSource?.name || '',
    offerId: rawClick.offerId || '',
    offerName: rawClick.offerName || '',
    landingId: rawClick.landingId || '',
    landingName: rawClick.landingName || '',
    affiliateNetworkId: rawClick.affiliateNetworkId || '',
    creativeId: rawClick.creativeId || '',
    adCampaignId: rawClick.adCampaignId || '',
    externalId: rawClick.externalId || '',
    isp: rawClick.isp || '',
    operator: rawClick.operator || '',
    connectionType: rawClick.connectionType || '',
    parentCampaignId: rawClick.parentCampaignId || '',
    token: rawClick.token || '',
    visitorCode: rawClick.visitorCode || '',
    sessionId: rawClick.sessionId || '',
    revenue: rawClick.saleRevenue?.toString() || rawClick.revenue?.toString() || '0',
    cost: rawClick.cost?.toString() || '0',
    payout: rawClick.payout?.toString() || rawClick.cost?.toString() || '0',
    profit: rawClick.profit?.toString(),
    status: rawClick.status || '',
    originalStatus: rawClick.originalStatus || '',
    tid: rawClick.tid || rawClick.transactionId || '',
    transactionId: rawClick.transactionId || rawClick.tid || '',
    currentDomain: rawClick.currentDomain || request?.headers?.get('host') || '',
    goal1: rawClick.goal1 || '0',
    goal2: rawClick.goal2 || '0',
    goal3: rawClick.goal3 || '0',
    goal4: rawClick.goal4 || '0',
    queryParams,
  };
}

/**
 * List of all supported macros for documentation/validation
 */
export const SUPPORTED_MACROS = [
  // Click identifiers
  '{clickid}', '{click_id}', '{cid}',
  
  // Campaign/Stream
  '{campaign_id}', '{campaignid}', '{campaign_name}',
  '{stream_id}', '{streamid}', '{stream_name}',
  '{parent_campaign_id}',
  
  // Traffic Source
  '{traffic_source_name}',
  
  // Publisher
  '{pub_id}', '{pubid}', '{subid}', '{sub_id}',
  
  // Sub IDs
  '{sub_id_1}', '{subid1}',
  '{sub_id_2}', '{subid2}',
  '{sub_id_3}', '{subid3}',
  '{sub_id_4}', '{subid4}',
  '{sub_id_5}', '{subid5}',
  
  // Geo
  '{ip}', '{country}', '{country_code}', '{region}', '{city}',
  
  // Device
  '{browser}', '{browser_version}',
  '{os}', '{os_version}',
  '{device}', '{device_type}', '{device_model}', '{device_brand}',
  '{user_agent}', '{ua}',
  
  // Connection/ISP
  '{isp}', '{operator}', '{connection_type}',
  
  // Traffic
  '{referrer}', '{referer}',
  '{keyword}', '{kw}',
  '{source}',
  
  // Domain
  '{current_domain}', '{domain}',
  
  // Timestamp
  '{timestamp}', '{date}', '{time}', '{datetime}',
  
  // Offer/Landing
  '{offer_id}', '{offer_name}',
  '{landing_id}', '{landing_name}',
  '{affiliate_network_id}',
  
  // Additional tracking
  '{creative_id}', '{ad_campaign_id}', '{external_id}',
  
  // Session/Visitor
  '{visitor_code}', '{session_id}', '{token}',
  
  // Revenue/Cost/Profit
  '{revenue}', '{cost}', '{payout}', '{profit}',
  
  // Conversion status
  '{status}', '{original_status}',
  
  // Transaction ID
  '{tid}', '{transaction_id}',
  
  // Goals
  '{goal_1}', '{goal_2}', '{goal_3}', '{goal_4}',
  
  // Random
  '{random}', '{rand}',
  
  // Functional macros
  '{sample:percent}',  // e.g., {sample:50} returns 'true' 50% of the time
  '{from_file:filename}', // e.g., {from_file:offers.txt}
  
  // Raw mode (no URL encoding)
  '{_clickid}', '{_campaign_name}', '{_keyword}', // etc.
  
  // Query parameters (any param from the request)
  '{any_query_param_name}',
] as const;

/**
 * Validate if a macro is supported
 */
export function isValidMacro(macro: string): boolean {
  // Remove braces
  const name = macro.replace(/[{}]/g, '');
  
  // Check if it starts with _ (raw mode)
  const actualName = name.startsWith('_') ? name.substring(1) : name;
  
  // Check functional macros
  if (actualName.includes(':')) {
    const [funcName] = actualName.split(':');
    return ['sample', 'from_file'].includes(funcName.toLowerCase());
  }
  
  // Check against supported macros (case-insensitive)
  const lowerMacro = `{${actualName.toLowerCase()}}`;
  return SUPPORTED_MACROS.some(m => m.toLowerCase() === lowerMacro);
}
