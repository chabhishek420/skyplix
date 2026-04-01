/**
 * IP Info Service
 * Resolves geo, ISP, and connection type information from IP addresses
 * Based on Keitaro's IpInfoService.php
 */

import type { GeoInfo } from '../pipeline/types';
import { geoDbService } from './geo-db-service';


// GeoIP database configuration
const GEOIP_ENABLED = process.env.GEOIP_ENABLED !== 'false';

/**
 * IP Info Result
 */
export interface IpInfoResult {
  ip: string;
  country: string | null;
  region: string | null;
  city: string | null;
  isp: string | null;
  operator: string | null;
  connectionType: string | null;
  timezone: string | null;
  latitude: number | null;
  longitude: number | null;
  isProxy: boolean;
  isVpn: boolean;
  isTor: boolean;
  isHosting: boolean;
}

/**
 * IP Info Service
 * Singleton service for IP information resolution
 */
class IpInfoService {
  private cache: Map<string, { data: IpInfoResult; timestamp: number }> = new Map();
  private cacheTtl = 3600000; // 1 hour cache

  /**
   * Get IP info from cache or resolve
   */
  async getIpInfo(ip: string): Promise<IpInfoResult> {
    // Check cache first
    const cached = this.cache.get(ip);
    if (cached && Date.now() - cached.timestamp < this.cacheTtl) {
      return cached.data;
    }

    // Resolve IP info
    const result = await this.resolveIpInfo(ip);

    // Cache result
    this.cache.set(ip, { data: result, timestamp: Date.now() });

    return result;
  }

  /**
   * Resolve IP info from GeoIP database or API
   */
  private async resolveIpInfo(ip: string): Promise<IpInfoResult> {
    const result: IpInfoResult = {
      ip,
      country: null,
      region: null,
      city: null,
      isp: null,
      operator: null,
      connectionType: null,
      timezone: null,
      latitude: null,
      longitude: null,
      isProxy: false,
      isVpn: false,
      isTor: false,
      isHosting: false
    };

    if (!GEOIP_ENABLED) {
      return result;
    }

    try {
      // Try local GeoIP lookup first
      const geoData = await this.localGeoIpLookup(ip);
      if (geoData) {
        Object.assign(result, geoData);
      }

      // Check for proxy/VPN
      const proxyData = await this.checkProxyStatus(ip);
      if (proxyData) {
        result.isProxy = proxyData.isProxy;
        result.isVpn = proxyData.isVpn;
        result.isTor = proxyData.isTor;
        result.isHosting = proxyData.isHosting;
      }
    } catch (error) {
      console.warn('IP info resolution failed:', error);
    }

    return result;
  }

  /**
   * Local GeoIP lookup — delegates to geoDbService (MaxMind + ip-api fallback)
   */
  private async localGeoIpLookup(ip: string): Promise<Partial<IpInfoResult> | null> {
    // Skip private IPs
    if (this.isPrivateIp(ip)) {
      return {
        country: 'LOCAL',
        region: 'Local',
        city: 'Local',
        isp: 'Local Network',
        operator: 'Local',
        timezone: 'UTC'
      };
    }

    try {
      // Delegate to geoDbService which handles MaxMind → ip-api fallback
      const geo = await geoDbService.getGeoIp(ip);
      return {
        country: geo.countryCode || null,
        region: geo.region || null,
        city: geo.city || null,
        isp: geo.isp || null,
        operator: geo.organization || null,
        connectionType: geo.connectionType || null,
        latitude: geo.latitude,
        longitude: geo.longitude,
        timezone: geo.timezone,
        isProxy: geo.isProxy,
        isVpn: geo.isVpn,
        isTor: geo.isTor,
        isHosting: geo.isHosting,
      };
    } catch (error) {
      return null;
    }
  }


  /**
   * Check proxy status
   */
  private async checkProxyStatus(ip: string): Promise<{
    isProxy: boolean;
    isVpn: boolean;
    isTor: boolean;
    isHosting: boolean;
  } | null> {
    // In production, use services like:
    // - IPQualityScore
    // - ProxyCheck.io
    // - IPHub
    // For now, return basic detection
    
    return {
      isProxy: false,
      isVpn: false,
      isTor: false,
      isHosting: false
    };
  }

  /**
   * Check if IP is in CIDR range
   */
  isIpInCidr(ip: string, cidr: string): boolean {
    const [range, bits] = cidr.split('/');
    const mask = parseInt(bits, 10);
    
    const ipNum = this.ipToNumber(ip);
    const rangeNum = this.ipToNumber(range);
    
    if (ipNum === null || rangeNum === null) return false;
    
    const maskNum = ~((1 << (32 - mask)) - 1);
    return (ipNum & maskNum) === (rangeNum & maskNum);
  }

  /**
   * Convert IP to number
   */
  private ipToNumber(ip: string): number | null {
    const parts = ip.split('.').map(p => parseInt(p, 10));
    if (parts.length !== 4 || parts.some(isNaN)) return null;
    return (parts[0] << 24) + (parts[1] << 16) + (parts[2] << 8) + parts[3];
  }

  /**
   * Check if IP is private/local
   */
  isPrivateIp(ip: string): boolean {
    const privateRanges = [
      '10.0.0.0/8',
      '172.16.0.0/12',
      '192.168.0.0/16',
      '127.0.0.0/8',
      '169.254.0.0/16'
    ];

    return privateRanges.some(range => this.isIpInCidr(ip, range));
  }

  /**
   * Get country code from IP
   */
  async getCountry(ip: string): Promise<string | null> {
    const info = await this.getIpInfo(ip);
    return info.country;
  }

  /**
   * Get city from IP
   */
  async getCity(ip: string): Promise<string | null> {
    const info = await this.getIpInfo(ip);
    return info.city;
  }

  /**
   * Get full geo info
   */
  async getGeoInfo(ip: string): Promise<GeoInfo | null> {
    const info = await this.getIpInfo(ip);
    
    if (!info.country) {
      return null;
    }

    return {
      country: info.country,
      region: info.region || '',
      city: info.city || '',
      isp: info.isp || '',
      operator: info.operator || '',
      connectionType: info.connectionType || ''
    };
  }

  /**
   * Clear cache
   */
  clearCache(): void {
    this.cache.clear();
  }

  /**
   * Cleanup old cache entries
   */
  cleanupCache(): void {
    const now = Date.now();
    for (const [key, value] of this.cache.entries()) {
      if (now - value.timestamp > this.cacheTtl) {
        this.cache.delete(key);
      }
    }
  }
}

// Export singleton instance
export const ipInfoService = new IpInfoService();
