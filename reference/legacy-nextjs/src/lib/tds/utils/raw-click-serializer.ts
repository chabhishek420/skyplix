/**
 * RawClick Serialization Utility
 * 
 * Based on Keitaro PHP RawClick::serialize()
 * Converts RawClick object to database-friendly format
 */

import type { RawClick } from '../pipeline/types';

// Constants matching PHP
const REFERRER_LIMIT = 250;
const DESTINATION_LIMIT = 250;
const SUB_ID_COUNT = 15; // Keitaro has 15 sub IDs
const EXTRA_PARAM_COUNT = 3;

/**
 * Serialized click data for database storage
 */
export interface SerializedClick {
  // Core identifiers
  clickId: string;
  visitorCode: string | null;
  campaignId: string | null;
  streamId: string | null;
  landingId: string | null;
  offerId: string | null;
  affiliateNetworkId: string | null;
  
  // Request data
  ip: string | null;
  ipString: string | null;
  userAgent: string | null;
  referrer: string | null;
  language: string | null;
  
  // Geo data (only if resolved)
  country: string | null;
  region: string | null;
  city: string | null;
  isp: string | null;
  operator: string | null;
  connectionType: string | null;
  
  // Device data (only if resolved)
  browser: string | null;
  browserVersion: string | null;
  os: string | null;
  osVersion: string | null;
  deviceType: string | null;
  deviceModel: string | null;
  deviceBrand: string | null;
  isMobile: boolean;
  
  // Traffic data
  source: string | null;
  keyword: string | null;
  searchEngine: string | null;
  xRequestedWith: string | null;
  
  // Sub IDs (1-15)
  subId: string | null;
  subId1: string | null;
  subId2: string | null;
  subId3: string | null;
  subId4: string | null;
  subId5: string | null;
  subId6: string | null;
  subId7: string | null;
  subId8: string | null;
  subId9: string | null;
  subId10: string | null;
  subId11: string | null;
  subId12: string | null;
  subId13: string | null;
  subId14: string | null;
  subId15: string | null;
  
  // Extra params
  extraParam1: string | null;
  extraParam2: string | null;
  extraParam3: string | null;
  
  // Destination
  destination: string | null;
  landingUrl: string | null;
  
  // Detection flags
  isBot: boolean;
  botReason: string | null;
  botType: string | null;
  isUsingProxy: boolean;
  isEmptyReferrer: boolean;
  
  // Uniqueness flags
  isUniqueCampaign: boolean;
  isUniqueStream: boolean;
  isUniqueGlobal: boolean;
  
  // Resolution flags
  isGeoResolved: boolean;
  isDeviceResolved: boolean;
  isIspResolved: boolean;
  
  // Revenue tracking
  isLead: boolean;
  isSale: boolean;
  isRejected: boolean;
  leadRevenue: number | null;
  saleRevenue: number | null;
  rejectedRevenue: number | null;
  cost: number | null;
  
  // Tracking
  parentCampaignId: string | null;
  parentSubId: string | null;
  token: string | null;
  creativeId: string | null;
  adCampaignId: string | null;
  externalId: string | null;
  tsId: string | null;
  
  // Timestamp
  datetime: string; // ISO format
  
  // Session
  sessionId: string | null;
}

/**
 * Serialize RawClick for database storage
 * Matches PHP RawClick::serialize()
 */
export function serializeRawClick(rawClick: RawClick): SerializedClick {
  // Truncate referrer
  const referrer = rawClick.referrer 
    ? rawClick.referrer.substring(0, REFERRER_LIMIT)
    : null;
  
  // Truncate destination
  const destination = rawClick.destination
    ? rawClick.destination.substring(0, DESTINATION_LIMIT)
    : null;
  
  const serialized: SerializedClick = {
    // Core identifiers
    clickId: rawClick.clickId,
    visitorCode: rawClick.visitorCode || null,
    campaignId: rawClick.campaignId || null,
    streamId: rawClick.streamId || null,
    landingId: rawClick.landingId || null,
    offerId: rawClick.offerId || null,
    affiliateNetworkId: rawClick.affiliateNetworkId || null,
    
    // Request data
    ip: rawClick.ip || null,
    ipString: rawClick.ipString || null,
    userAgent: rawClick.userAgent || null,
    referrer,
    language: rawClick.language ? rawClick.language.substring(0, 2).toUpperCase() : null,
    
    // Geo data (only if resolved)
    country: rawClick.isGeoResolved ? rawClick.country : null,
    region: rawClick.isGeoResolved ? rawClick.region : null,
    city: rawClick.isGeoResolved ? rawClick.city : null,
    isp: rawClick.isIspResolved ? rawClick.isp : null,
    operator: rawClick.isIspResolved ? rawClick.operator : null,
    connectionType: rawClick.isIspResolved ? rawClick.connectionType : null,
    
    // Device data (only if resolved)
    browser: rawClick.isDeviceResolved ? rawClick.browser : null,
    browserVersion: rawClick.isDeviceResolved ? rawClick.browserVersion : null,
    os: rawClick.isDeviceResolved ? rawClick.os : null,
    osVersion: rawClick.isDeviceResolved ? rawClick.osVersion : null,
    deviceType: rawClick.isDeviceResolved ? rawClick.deviceType : null,
    deviceModel: rawClick.isDeviceResolved ? rawClick.deviceModel : null,
    deviceBrand: rawClick.isDeviceResolved ? rawClick.deviceBrand : null,
    isMobile: rawClick.isMobile,
    
    // Traffic data
    source: rawClick.source || null,
    keyword: rawClick.keyword || null,
    searchEngine: rawClick.searchEngine || null,
    xRequestedWith: rawClick.xRequestedWith || null,
    
    // Sub IDs
    subId: rawClick.subId || null,
    subId1: rawClick.subId1 || null,
    subId2: rawClick.subId2 || null,
    subId3: rawClick.subId3 || null,
    subId4: rawClick.subId4 || null,
    subId5: rawClick.subId5 || null,
    subId6: rawClick.subId6 || null,
    subId7: rawClick.subId7 || null,
    subId8: rawClick.subId8 || null,
    subId9: rawClick.subId9 || null,
    subId10: rawClick.subId10 || null,
    subId11: rawClick.subId11 || null,
    subId12: rawClick.subId12 || null,
    subId13: rawClick.subId13 || null,
    subId14: rawClick.subId14 || null,
    subId15: rawClick.subId15 || null,
    
    // Extra params
    extraParam1: rawClick.extraParam1 || null,
    extraParam2: rawClick.extraParam2 || null,
    extraParam3: rawClick.extraParam3 || null,
    
    // Destination
    destination,
    landingUrl: rawClick.landingUrl || null,
    
    // Detection flags
    isBot: rawClick.isBot,
    botReason: rawClick.botReason || null,
    botType: rawClick.botType || null,
    isUsingProxy: rawClick.isUsingProxy,
    isEmptyReferrer: !rawClick.referrer,
    
    // Uniqueness flags
    isUniqueCampaign: rawClick.isUniqueCampaign,
    isUniqueStream: rawClick.isUniqueStream,
    isUniqueGlobal: rawClick.isUniqueGlobal,
    
    // Resolution flags
    isGeoResolved: rawClick.isGeoResolved,
    isDeviceResolved: rawClick.isDeviceResolved,
    isIspResolved: rawClick.isIspResolved,
    
    // Revenue tracking
    isLead: rawClick.isLead,
    isSale: rawClick.isSale,
    isRejected: rawClick.isRejected,
    leadRevenue: rawClick.leadRevenue || null,
    saleRevenue: rawClick.saleRevenue || null,
    rejectedRevenue: rawClick.rejectedRevenue || null,
    cost: rawClick.cost || null,
    
    // Tracking
    parentCampaignId: rawClick.parentCampaignId || null,
    parentSubId: rawClick.parentSubId || null,
    token: rawClick.token || null,
    creativeId: rawClick.creativeId || null,
    adCampaignId: rawClick.adCampaignId || null,
    externalId: rawClick.externalId || null,
    tsId: null, // Traffic source ID
    
    // Timestamp
    datetime: rawClick.datetime instanceof Date 
      ? rawClick.datetime.toISOString() 
      : new Date().toISOString(),
    
    // Session
    sessionId: rawClick.sessionId || null
  };
  
  return serialized;
}

/**
 * Get filtered destination (for logging)
 * Removes sensitive tokens
 */
export function getFilteredDestination(rawClick: RawClick): string | null {
  if (!rawClick.destination) return null;
  
  let dest = rawClick.destination;
  
  // Remove _token parameter from display
  dest = dest.replace(/_token=[^&]*/g, '_token=[filtered]');
  
  // Truncate if needed
  if (dest.length > DESTINATION_LIMIT) {
    dest = dest.substring(0, DESTINATION_LIMIT) + '...';
  }
  
  return dest;
}

/**
 * Create RawClick from request data
 */
export function createRawClick(data: Partial<RawClick>): RawClick {
  const now = new Date();
  
  return {
    clickId: data.clickId || '',
    visitorCode: data.visitorCode || '',
    campaignId: data.campaignId || null,
    streamId: data.streamId || null,
    landingId: data.landingId || null,
    offerId: data.offerId || null,
    affiliateNetworkId: data.affiliateNetworkId || null,
    
    ip: data.ip || '',
    ipString: data.ipString || data.ip || '',
    userAgent: data.userAgent || '',
    referrer: data.referrer || null,
    language: data.language || null,
    
    country: data.country || null,
    region: data.region || null,
    city: data.city || null,
    isp: data.isp || null,
    operator: data.operator || null,
    connectionType: data.connectionType || null,
    
    browser: data.browser || null,
    browserVersion: data.browserVersion || null,
    os: data.os || null,
    osVersion: data.osVersion || null,
    deviceType: data.deviceType || null,
    deviceModel: data.deviceModel || null,
    deviceBrand: data.deviceBrand || null,
    isMobile: data.isMobile || false,
    
    source: data.source || null,
    keyword: data.keyword || null,
    searchEngine: data.searchEngine || null,
    xRequestedWith: data.xRequestedWith || null,
    subId: data.subId || null,
    subId1: data.subId1 || null,
    subId2: data.subId2 || null,
    subId3: data.subId3 || null,
    subId4: data.subId4 || null,
    subId5: data.subId5 || null,
    extraParam1: data.extraParam1 || null,
    extraParam2: data.extraParam2 || null,
    extraParam3: data.extraParam3 || null,
    
    destination: data.destination || null,
    landingUrl: data.landingUrl || null,
    
    isBot: data.isBot || false,
    botReason: data.botReason || null,
    botType: data.botType || null,
    isUsingProxy: data.isUsingProxy || false,
    
    isUniqueCampaign: data.isUniqueCampaign ?? true,
    isUniqueStream: data.isUniqueStream ?? true,
    isUniqueGlobal: data.isUniqueGlobal ?? true,
    
    isGeoResolved: data.isGeoResolved || false,
    isDeviceResolved: data.isDeviceResolved || false,
    isIspResolved: data.isIspResolved || false,
    
    isLead: data.isLead || false,
    isSale: data.isSale || false,
    isRejected: data.isRejected || false,
    leadRevenue: data.leadRevenue || null,
    saleRevenue: data.saleRevenue || null,
    rejectedRevenue: data.rejectedRevenue || null,
    cost: data.cost || null,
    
    parentCampaignId: data.parentCampaignId || null,
    parentSubId: data.parentSubId || null,
    token: data.token || null,
    creativeId: data.creativeId || null,
    adCampaignId: data.adCampaignId || null,
    externalId: data.externalId || null,
    
    sessionId: data.sessionId || null,
    datetime: data.datetime || now
  };
}
