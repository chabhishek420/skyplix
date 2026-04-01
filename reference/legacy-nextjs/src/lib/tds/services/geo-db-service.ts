/**
 * GeoDb Service
 * GeoIP resolution using MaxMind GeoIP2 databases
 * Falls back to IP-API for development
 */

import path from 'path';
import fs from 'fs';

// GeoIP result interface
export interface GeoIpResult {
  country: string | null;
  countryCode: string | null;
  region: string | null;
  regionCode: string | null;
  city: string | null;
  latitude: number | null;
  longitude: number | null;
  timezone: string | null;
  isp: string | null;
  organization: string | null;
  asn: number | null;
  asOrganization: string | null;
  connectionType: 'dialup' | 'cable' | 'corporate' | 'cellular' | null;
  isProxy: boolean;
  isTor: boolean;
  isVpn: boolean;
  isHosting: boolean;
}

// Cache entry
interface CacheEntry {
  result: GeoIpResult;
  timestamp: number;
}

// In-memory cache
const geoCache = new Map<string, CacheEntry>();
const CACHE_TTL = 3600000; // 1 hour

// MaxMind reader (lazy loaded)
let maxMindReader: any = null;
let ispReader: any = null;
let asnReader: any = null;

/**
 * Check if MaxMind databases are available
 */
function hasMaxMindDatabases(): boolean {
  const dbPath = process.env.MAXMIND_DB_PATH || './geodb';
  const cityDb = path.join(dbPath, 'GeoIP2-City.mmdb');
  const ispDb = path.join(dbPath, 'GeoIP2-ISP.mmdb');
  
  return fs.existsSync(cityDb);
}

/**
 * Initialize MaxMind readers
 */
async function initMaxMind(): Promise<void> {
  if (maxMindReader) return;

  try {
    // Dynamic import for MaxMind (optional dependency)
    // Dynamic import for MaxMind (optional production dependency — no types in dev)
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-expect-error maxmind is optional — absence is handled by .catch()
    const maxmind = await import('maxmind').catch(() => null);

    
    if (!maxmind) {
      console.warn('MaxMind module not available, using IP-API fallback');
      return;
    }

    const dbPath = process.env.MAXMIND_DB_PATH || './geodb';
    
    // City database
    const cityDbPath = path.join(dbPath, 'GeoIP2-City.mmdb');
    if (fs.existsSync(cityDbPath)) {
      maxMindReader = await maxmind.default.open(cityDbPath);
    }

    // ISP database
    const ispDbPath = path.join(dbPath, 'GeoIP2-ISP.mmdb');
    if (fs.existsSync(ispDbPath)) {
      ispReader = await maxmind.default.open(ispDbPath);
    }

    // ASN database
    const asnDbPath = path.join(dbPath, 'GeoLite2-ASN.mmdb');
    if (fs.existsSync(asnDbPath)) {
      asnReader = await maxmind.default.open(asnDbPath);
    }
  } catch (error) {
    console.error('Failed to initialize MaxMind:', error);
  }
}

/**
 * Look up IP using MaxMind databases
 */
async function lookupMaxMind(ip: string): Promise<GeoIpResult | null> {
  await initMaxMind();

  if (!maxMindReader) {
    return null;
  }

  try {
    const cityResult = maxMindReader.get(ip);
    const ispResult = ispReader?.get(ip);
    const asnResult = asnReader?.get(ip);

    if (!cityResult) {
      return null;
    }

    // Parse MaxMind result
    const result: GeoIpResult = {
      country: cityResult.city?.names?.en || null,
      countryCode: cityResult.country?.iso_code || null,
      region: cityResult.subdivisions?.[0]?.names?.en || null,
      regionCode: cityResult.subdivisions?.[0]?.iso_code || null,
      city: cityResult.city?.names?.en || null,
      latitude: cityResult.location?.latitude || null,
      longitude: cityResult.location?.longitude || null,
      timezone: cityResult.location?.time_zone || null,
      isp: ispResult?.isp || null,
      organization: ispResult?.organization || null,
      asn: asnResult?.autonomous_system_number || null,
      asOrganization: asnResult?.autonomous_system_organization || null,
      connectionType: cityResult.traits?.connectionType as GeoIpResult['connectionType'] || null,
      isProxy: cityResult.traits?.isProxy || false,
      isTor: cityResult.traits?.isTorExitNode || false,
      isVpn: false, // Requires GeoIP2-Anonymous-IP database
      isHosting: cityResult.traits?.isHostingProvider || false
    };

    return result;
  } catch (error) {
    console.error('MaxMind lookup error:', error);
    return null;
  }
}

/**
 * Look up IP using IP-API (free, for development)
 */
async function lookupIpApi(ip: string): Promise<GeoIpResult | null> {
  try {
    const response = await fetch(`http://ip-api.com/json/${ip}?fields=status,country,countryCode,region,regionName,city,lat,lon,timezone,isp,org,as,proxy,hosting,query`);
    
    if (!response.ok) {
      return null;
    }

    const data = await response.json();

    if (data.status !== 'success') {
      return null;
    }

    return {
      country: data.country || null,
      countryCode: data.countryCode || null,
      region: data.regionName || null,
      regionCode: data.region || null,
      city: data.city || null,
      latitude: data.lat || null,
      longitude: data.lon || null,
      timezone: data.timezone || null,
      isp: data.isp || null,
      organization: data.org || null,
      asn: data.as ? parseInt(data.as.split(' ')[0]) : null,
      asOrganization: data.as?.split(' ').slice(1).join(' ') || null,
      connectionType: null,
      isProxy: data.proxy || false,
      isTor: false,
      isVpn: false,
      isHosting: data.hosting || false
    };
  } catch (error) {
    console.error('IP-API lookup error:', error);
    return null;
  }
}

/**
 * Get GeoIP information for an IP address
 * Uses MaxMind if available, falls back to IP-API
 */
export async function getGeoIp(ip: string): Promise<GeoIpResult> {
  // Check cache first
  const cached = geoCache.get(ip);
  if (cached && Date.now() - cached.timestamp < CACHE_TTL) {
    return cached.result;
  }

  // Try MaxMind first
  let result = await lookupMaxMind(ip);

  // Fall back to IP-API
  if (!result && process.env.NODE_ENV !== 'production') {
    result = await lookupIpApi(ip);
  }

  // Return default if all lookups fail
  if (!result) {
    result = {
      country: null,
      countryCode: null,
      region: null,
      regionCode: null,
      city: null,
      latitude: null,
      longitude: null,
      timezone: null,
      isp: null,
      organization: null,
      asn: null,
      asOrganization: null,
      connectionType: null,
      isProxy: false,
      isTor: false,
      isVpn: false,
      isHosting: false
    };
  }

  // Cache result
  geoCache.set(ip, { result, timestamp: Date.now() });

  return result;
}

/**
 * Check if IP is from a proxy/VPN/Tor
 */
export async function isProxyIp(ip: string): Promise<boolean> {
  const geo = await getGeoIp(ip);
  return geo.isProxy || geo.isTor || geo.isVpn || geo.isHosting;
}

/**
 * Get connection type for IP
 */
export async function getConnectionType(ip: string): Promise<string | null> {
  const geo = await getGeoIp(ip);
  return geo.connectionType;
}

/**
 * Get ISP for IP
 */
export async function getIsp(ip: string): Promise<string | null> {
  const geo = await getGeoIp(ip);
  return geo.isp || geo.organization;
}

/**
 * Clear geo cache
 */
export function clearGeoCache(): void {
  geoCache.clear();
}

/**
 * Get cache stats
 */
export function getGeoCacheStats(): { size: number; hitRate: number } {
  return {
    size: geoCache.size,
    hitRate: 0 // Would need to track hits/misses
  };
}

// Export singleton service
export const geoDbService = {
  getGeoIp,
  isProxyIp,
  getConnectionType,
  getIsp,
  clearGeoCache,
  getGeoCacheStats,
  hasMaxMindDatabases
};
