/**
 * Cookies Service
 * Manages tracking cookies for visitor identification
 * Based on Keitaro's CookiesService.php
 */

import type { NextRequest, NextResponse } from 'next/server';

/**
 * Cookie entry for uniqueness tracking
 */
export interface CookieEntry {
  campaignIds: string[];
  streamIds: string[];
  landingIds: string[];
  offerIds: string[];
  firstVisit: number;
  lastVisit: number;
  visitCount: number;
}

/**
 * Cookie configuration
 */
export interface CookieConfig {
  name: string;
  ttl: number; // hours
  secure: boolean;
  httpOnly: boolean;
  sameSite: 'strict' | 'lax' | 'none';
  domain?: string;
  path: string;
}

/**
 * Default cookie configurations
 */
const DEFAULT_COOKIE_CONFIGS: Record<string, CookieConfig> = {
  session: {
    name: 'sess',
    ttl: 168, // 7 days
    secure: true,
    httpOnly: true,
    sameSite: 'lax',
    path: '/'
  },
  visitor: {
    name: 'visitor',
    ttl: 8760, // 1 year
    secure: true,
    httpOnly: true,
    sameSite: 'lax',
    path: '/'
  },
  binding: {
    name: 'bind',
    ttl: 720, // 30 days
    secure: true,
    httpOnly: true,
    sameSite: 'lax',
    path: '/'
  },
  uniqueness: {
    name: 'uniq',
    ttl: 720, // 30 days
    secure: true,
    httpOnly: true,
    sameSite: 'lax',
    path: '/'
  }
};

/**
 * Cookies Service
 */
class CookiesService {
  /**
   * Get cookie value from request
   */
  getCookie(request: NextRequest, name: string): string | null {
    const cookieName = DEFAULT_COOKIE_CONFIGS[name]?.name || name;
    return request.cookies.get(cookieName)?.value || null;
  }

  /**
   * Get all cookies from request
   */
  getAllCookies(request: NextRequest): Record<string, string> {
    const cookies: Record<string, string> = {};
    for (const cookie of request.cookies.getAll()) {
      cookies[cookie.name] = cookie.value;
    }

    return cookies;
  }

  /**
   * Set cookie on response
   */
  setCookie(
    response: NextResponse,
    name: string,
    value: string,
    customConfig?: Partial<CookieConfig>
  ): void {
    const config = { ...DEFAULT_COOKIE_CONFIGS[name], ...customConfig } as CookieConfig;
    
    const cookieName = config.name;
    const maxAge = config.ttl * 3600; // Convert hours to seconds
    
    response.cookies.set(cookieName, value, {
      maxAge,
      secure: config.secure,
      httpOnly: config.httpOnly,
      sameSite: config.sameSite,
      domain: config.domain,
      path: config.path
    });
  }

  /**
   * Format cookie as header string
   */
  formatCookie(
    name: string,
    value: string,
    customConfig?: Partial<CookieConfig>
  ): string {
    const config = { ...DEFAULT_COOKIE_CONFIGS[name], ...customConfig } as CookieConfig;
    const items = [`${config.name}=${value}`, `Path=${config.path}`];
    
    if (config.ttl) items.push(`Max-Age=${config.ttl * 3600}`);
    if (config.domain) items.push(`Domain=${config.domain}`);
    if (config.secure) items.push('Secure');
    if (config.httpOnly) items.push('HttpOnly');
    if (config.sameSite) items.push(`SameSite=${config.sameSite.charAt(0).toUpperCase() + config.sameSite.slice(1)}`);
    
    return items.join('; ');
  }


  /**
   * Delete cookie
   */
  deleteCookie(response: NextResponse, name: string): void {
    const config = DEFAULT_COOKIE_CONFIGS[name];
    if (config) {
      response.cookies.delete(config.name);
    } else {
      response.cookies.delete(name);
    }
  }

  /**
   * Parse cookie entry from value
   */
  parseCookieEntry(value: string): CookieEntry | null {
    try {
      const decoded = Buffer.from(value, 'base64').toString('utf-8');
      const parsed = JSON.parse(decoded);
      
      return {
        campaignIds: parsed.campaignIds || [],
        streamIds: parsed.streamIds || [],
        landingIds: parsed.landingIds || [],
        offerIds: parsed.offerIds || [],
        firstVisit: parsed.firstVisit || Date.now(),
        lastVisit: parsed.lastVisit || Date.now(),
        visitCount: parsed.visitCount || 1
      };
    } catch {
      return null;
    }
  }

  /**
   * Encode cookie entry to value
   */
  encodeCookieEntry(entry: CookieEntry): string {
    const json = JSON.stringify(entry);
    return Buffer.from(json).toString('base64');
  }

  /**
   * Get uniqueness cookie entry
   */
  getUniquenessEntry(request: NextRequest, uniquenessId: string): CookieEntry | null {
    const cookieName = `uniq_${uniquenessId.substring(0, 8)}`;
    const value = this.getCookie(request, cookieName);
    
    if (!value) return null;
    
    return this.parseCookieEntry(value);
  }

  /**
   * Set uniqueness cookie entry
   */
  setUniquenessEntry(
    response: NextResponse,
    uniquenessId: string,
    entry: CookieEntry
  ): void {
    const cookieName = `uniq_${uniquenessId.substring(0, 8)}`;
    const value = this.encodeCookieEntry(entry);
    
    this.setCookie(response, 'uniqueness', value, {
      name: cookieName
    });
  }

  /**
   * Update uniqueness entry with new visit
   */
  updateUniquenessEntry(
    request: NextRequest,
    response: NextResponse,
    uniquenessId: string,
    campaignId?: string,
    streamId?: string,
    landingId?: string,
    offerId?: string
  ): CookieEntry {
    let entry = this.getUniquenessEntry(request, uniquenessId);
    
    if (!entry) {
      entry = {
        campaignIds: [],
        streamIds: [],
        landingIds: [],
        offerIds: [],
        firstVisit: Date.now(),
        lastVisit: Date.now(),
        visitCount: 0
      };
    }
    
    // Update entry
    entry.lastVisit = Date.now();
    entry.visitCount++;
    
    if (campaignId && !entry.campaignIds.includes(campaignId)) {
      entry.campaignIds.push(campaignId);
    }
    if (streamId && !entry.streamIds.includes(streamId)) {
      entry.streamIds.push(streamId);
    }
    if (landingId && !entry.landingIds.includes(landingId)) {
      entry.landingIds.push(landingId);
    }
    if (offerId && !entry.offerIds.includes(offerId)) {
      entry.offerIds.push(offerId);
    }
    
    this.setUniquenessEntry(response, uniquenessId, entry);
    
    return entry;
  }

  /**
   * Check if visitor is unique for campaign
   */
  isUniqueForCampaign(request: NextRequest, uniquenessId: string, campaignId: string): boolean {
    const entry = this.getUniquenessEntry(request, uniquenessId);
    return !entry || !entry.campaignIds.includes(campaignId);
  }

  /**
   * Check if visitor is unique for stream
   */
  isUniqueForStream(request: NextRequest, uniquenessId: string, streamId: string): boolean {
    const entry = this.getUniquenessEntry(request, uniquenessId);
    return !entry || !entry.streamIds.includes(streamId);
  }

  /**
   * Check if visitor is unique globally
   */
  isUniqueGlobal(request: NextRequest, uniquenessId: string): boolean {
    const entry = this.getUniquenessEntry(request, uniquenessId);
    return !entry;
  }

  /**
   * Get session ID from cookie
   */
  getSessionId(request: NextRequest): string | null {
    return this.getCookie(request, 'session');
  }

  /**
   * Set session ID cookie
   */
  setSessionId(response: NextResponse, sessionId: string): void {
    this.setCookie(response, 'session', sessionId);
  }

  /**
   * Get visitor ID from cookie
   */
  getVisitorId(request: NextRequest): string | null {
    return this.getCookie(request, 'visitor');
  }

  /**
   * Set visitor ID cookie
   */
  setVisitorId(response: NextResponse, visitorId: string): void {
    this.setCookie(response, 'visitor', visitorId);
  }

  /**
   * Get binding cookie (for stream/landing/offer binding)
   */
  getBinding(request: NextRequest): Record<string, string> | null {
    const value = this.getCookie(request, 'binding');
    if (!value) return null;
    
    try {
      return JSON.parse(Buffer.from(value, 'base64').toString('utf-8'));
    } catch {
      return null;
    }
  }

  /**
   * Set binding cookie
   */
  setBinding(
    response: NextResponse,
    campaignId?: string,
    streamId?: string,
    landingId?: string,
    offerId?: string
  ): void {
    const binding: Record<string, string> = {};
    if (campaignId) binding.campaignId = campaignId;
    if (streamId) binding.streamId = streamId;
    if (landingId) binding.landingId = landingId;
    if (offerId) binding.offerId = offerId;
    
    const value = Buffer.from(JSON.stringify(binding)).toString('base64');
    this.setCookie(response, 'binding', value);
  }
}

// Export singleton instance
export const cookiesService = new CookiesService();
