/**
 * BuildRawClickStage
 * 
 * Builds the RawClick object from request data.
 * Based on Keitaro's BuildRawClickStage.php
 * 
 * Processing steps:
 * 1. Prepare - Set datetime, user agent, IP
 * 2. Find language - Parse Accept-Language header
 * 3. Find referrer - HTTP Referer header or parameter
 * 4. Find SE referrer - Search engine referrer parameter
 * 5. Find source - Extract from referrer URL
 * 6. Find keyword - From parameters or referrer
 * 7. Find sub IDs - sub_id_1 through sub_id_N
 * 8. Find extra params - extra_param_1 through extra_param_N
 * 9. Resolve geo - Country, region, city (via IpInfoService)
 * 10. Resolve device - Browser, OS, device type
 * 11. Check if bot - User agent and IP analysis
 * 12. Check if proxy - Proxy/VPN detection
 */

import type { StageInterface, StageResult, PipelinePayload, RawClick, DeviceInfo, PipelinePayload } from '../types';

import { generateUniqueClickId } from '../../click-id';
import { detectBot } from '../../bot-detection';
import { ipInfoService, type IpInfoResult } from '../../services/ip-info-service';
import { proxyService, type ProxyDetectionResult } from '../../services/proxy-service';

// Device detection patterns
const BROWSER_PATTERNS: [RegExp, string][] = [
  [/Edge\/(\d+)/, 'Edge'],
  [/Edg\/(\d+)/, 'Edge'],
  [/OPR\/(\d+)/, 'Opera'],
  [/Opera.*Version\/(\d+)/, 'Opera'],
  [/Firefox\/(\d+)/, 'Firefox'],
  [/Chrome\/(\d+)/, 'Chrome'],
  [/Safari\/(\d+)/, 'Safari'],
  [/MSIE (\d+)/, 'IE'],
  [/Trident.*rv:(\d+)/, 'IE'],
];

const OS_PATTERNS: [RegExp, string][] = [
  [/Windows NT (\d+)/, 'Windows'],
  [/Mac OS X (\d+[._]\d+)/, 'MacOS'],
  [/Android (\d+)/, 'Android'],
  [/iPhone OS (\d+)/, 'iOS'],
  [/iPad.*OS (\d+)/, 'iOS'],
  [/Linux/, 'Linux'],
  [/Ubuntu/, 'Ubuntu'],
  [/Fedora/, 'Fedora'],
];

const MOBILE_PATTERNS = [
  /Mobile/i,
  /Android/i,
  /iPhone/i,
  /iPad/i,
  /Windows Phone/i,
  /BlackBerry/i,
  /Opera Mini/i,
  /IEMobile/i,
];

const TABLET_PATTERNS = [
  /iPad/i,
  /Android(?!.*Mobile)/i,
  /Tablet/i,
];

export class BuildRawClickStage implements StageInterface {
  name = 'BuildRawClickStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.request) {
      return {
        success: false,
        payload,
        error: 'Empty request',
        abort: true
      };
    }

    const request = payload.request;
    const params = payload.getAllParams();
    const headers = this.extractHeaders(request);

    // Build raw click with basic info
    const rawClick: RawClick = await this.buildRawClick(request, params);

    // Resolve IP info (geo, ISP, connection type) - INTEGRATED
    const ipInfo = await this.resolveIpInfo(rawClick.ipString);
    this.applyIpInfo(rawClick, ipInfo);

    // Bot detection - USING ASYNC VERSION
    const botDetection = await detectBot({
      userAgent: rawClick.userAgent,
      ip: rawClick.ipString,
      referrer: rawClick.referrer,
      headers: headers,
      params: params,
      cookies: {}
    });

    rawClick.isBot = botDetection.isBot && botDetection.confidence >= 70;
    rawClick.botReason = botDetection.reason;
    rawClick.botType = botDetection.botType;
    rawClick.botConfidence = botDetection.confidence;

    // Proxy detection - INTEGRATED
    const proxyDetection = await this.checkProxy(rawClick.ipString, headers);
    rawClick.isUsingProxy = proxyDetection.isProxy;

    // Device resolution
    const deviceInfo = this.resolveDeviceInfo(rawClick.userAgent);
    rawClick.browser = deviceInfo.browser;
    rawClick.browserVersion = deviceInfo.browserVersion;
    rawClick.os = deviceInfo.os;
    rawClick.osVersion = deviceInfo.osVersion;
    rawClick.deviceType = deviceInfo.deviceType;
    rawClick.deviceModel = deviceInfo.deviceModel;
    rawClick.deviceBrand = deviceInfo.deviceBrand;
    rawClick.isMobile = deviceInfo.isMobile;

    // Set uniqueness flags (default to true for new clicks)
    rawClick.isUniqueCampaign = true;
    rawClick.isUniqueStream = true;
    rawClick.isUniqueGlobal = true;

    // Set resolution flags
    rawClick.isGeoResolved = ipInfo.country !== null;
    rawClick.isDeviceResolved = true;
    rawClick.isIspResolved = ipInfo.isp !== null;

    payload.setRawClick(rawClick);
    payload.log(`Built RawClick: id=${rawClick.clickId}, ip=${rawClick.ipString}, country=${rawClick.country}, bot=${rawClick.isBot}, proxy=${rawClick.isUsingProxy}`);

    return {
      success: true,
      payload
    };
  }

  /**
   * Build RawClick from request
   */
  private async buildRawClick(request: Request, params: Record<string, string>): Promise<RawClick> {
    const userAgent = request.headers.get('user-agent') || '';
    const referrer = this.findReferrer(request, params);
    const seReferrer = this.findSeReferrer(request, params);
    const source = this.findSource(request, params, referrer);
    const keyword = this.findKeyword(request, params, referrer, seReferrer);

    return {
      // Core identifiers
      clickId: await generateUniqueClickId(),
      visitorCode: this.generateVisitorCode(),
      campaignId: null,
      streamId: null,
      landingId: null,
      offerId: null,
      affiliateNetworkId: null,
      
      // Request data
      ip: this.getIp(request),
      ipString: this.getIp(request),
      userAgent: userAgent,
      referrer: referrer,
      seReferrer: seReferrer,
      language: this.findLanguage(request),
      
      // Geo data (resolved later by resolveIpInfo)
      country: null,
      region: null,
      city: null,
      isp: null,
      operator: null,
      connectionType: null,
      
      // Device data
      browser: null,
      browserVersion: null,
      os: null,
      osVersion: null,
      deviceType: null,
      deviceModel: null,
      deviceBrand: null,
      isMobile: false,
      
      // Traffic data
      source: source,
      keyword: keyword,
      searchEngine: this.findSearchEngine(params, seReferrer, referrer),
      xRequestedWith: this.findXRequestedWith(request),
      // Sub IDs 1-15 (matching Keitaro's 15 sub ID support)
      subId: params.subid || params.sub_id || null,
      subId1: params.sub_id_1 || params.subid1 || params.s1 || null,
      subId2: params.sub_id_2 || params.subid2 || params.s2 || null,
      subId3: params.sub_id_3 || params.subid3 || params.s3 || null,
      subId4: params.sub_id_4 || params.subid4 || params.s4 || null,
      subId5: params.sub_id_5 || params.subid5 || params.s5 || null,
      subId6: params.sub_id_6 || params.subid6 || params.s6 || null,
      subId7: params.sub_id_7 || params.subid7 || params.s7 || null,
      subId8: params.sub_id_8 || params.subid8 || params.s8 || null,
      subId9: params.sub_id_9 || params.subid9 || params.s9 || null,
      subId10: params.sub_id_10 || params.subid10 || params.s10 || null,
      subId11: params.sub_id_11 || params.subid11 || params.s11 || null,
      subId12: params.sub_id_12 || params.subid12 || params.s12 || null,
      subId13: params.sub_id_13 || params.subid13 || params.s13 || null,
      subId14: params.sub_id_14 || params.subid14 || params.s14 || null,
      subId15: params.sub_id_15 || params.subid15 || params.s15 || null,
      
      // Extra params
      extraParam1: params.extra_param_1 || null,
      extraParam2: params.extra_param_2 || null,
      extraParam3: params.extra_param_3 || null,
      
      // Destination
      destination: null,
      landingUrl: params.landing_url || null,
      
      // Detection
      isBot: false,
      botReason: null,
      botType: null,
      botConfidence: 0,
      isUsingProxy: false,
      
      // Uniqueness
      isUniqueCampaign: false,
      isUniqueStream: false,
      isUniqueGlobal: false,
      
      // Resolution flags
      isGeoResolved: false,
      isDeviceResolved: false,
      isIspResolved: false,
      
      // Revenue
      isLead: false,
      isSale: false,
      isRejected: false,
      leadRevenue: null,
      saleRevenue: null,
      rejectedRevenue: null,
      cost: params.cost ? parseFloat(params.cost) : null,
      
      // Tracking
      parentCampaignId: null,
      parentSubId: null,
      token: null,
      creativeId: params.creative_id || null,
      adCampaignId: params.ad_campaign_id || null,
      externalId: params.external_id || null,
      
      // Timestamp
      datetime: new Date(),

      // Session
      sessionId: this.generateSessionId(),
    };
  }


  /**
   * Resolve IP info (geo, ISP, connection type)
   * Based on Keitaro's IpInfoService
   */
  private async resolveIpInfo(ip: string): Promise<IpInfoResult> {
    return ipInfoService.getIpInfo(ip);
  }

  /**
   * Apply IP info to RawClick
   */
  private applyIpInfo(rawClick: RawClick, ipInfo: IpInfoResult): void {
    rawClick.country = ipInfo.country;
    rawClick.region = ipInfo.region;
    rawClick.city = ipInfo.city;
    rawClick.isp = ipInfo.isp;
    rawClick.operator = ipInfo.operator;
    rawClick.connectionType = ipInfo.connectionType;
  }

  /**
   * Check if using proxy
   * Based on Keitaro's ProxyService
   */
  private async checkProxy(ip: string, headers: Record<string, string>): Promise<ProxyDetectionResult> {
    return proxyService.detectProxy(ip, headers);
  }

  /**
   * Find SE referrer (search engine referrer parameter)
   */
  private findSeReferrer(request: Request, params: Record<string, string>): string | null {
    if (params.se_referrer) {
      return decodeURIComponent(params.se_referrer);
    }
    return null;
  }

  /**
   * Find referrer
   */
  private findReferrer(request: Request, params: Record<string, string>): string | null {
    // Check for overridden referrer parameter
    if (params.referrer) {
      return decodeURIComponent(params.referrer);
    }
    if (params.referer) {
      return decodeURIComponent(params.referer);
    }
    
    // Check header
    const headerReferrer = request.headers.get('referer');
    return headerReferrer ? decodeURIComponent(headerReferrer) : null;
  }

  /**
   * Find source
   */
  private findSource(request: Request, params: Record<string, string>, referrer: string | null): string | null {
    // Check parameter
    if (params.source) {
      return params.source;
    }
    
    // Extract from referrer
    if (referrer) {
      try {
        const url = new URL(referrer);
        return url.hostname;
      } catch {
        // Invalid URL
      }
    }
    
    return null;
  }

  /**
   * Find keyword
   */
  private findKeyword(
    request: Request, 
    params: Record<string, string>, 
    referrer: string | null,
    seReferrer: string | null
  ): string | null {
    // Check parameter
    if (params.keyword) {
      return decodeURIComponent(params.keyword);
    }
    if (params.kw) {
      return decodeURIComponent(params.kw);
    }
    
    // Try to extract from SE referrer first
    if (seReferrer) {
      const keyword = this.extractKeywordFromReferrer(seReferrer);
      if (keyword) return keyword;
    }
    
    // Try to extract from regular referrer
    if (referrer) {
      const keyword = this.extractKeywordFromReferrer(referrer);
      if (keyword) return keyword;
    }
    
    // Default keyword
    return params.default_keyword ? decodeURIComponent(params.default_keyword) : null;
  }

  /**
   * Extract keyword from referrer URL
   */
  private extractKeywordFromReferrer(referrer: string): string | null {
    try {
      const url = new URL(referrer);
      const host = url.hostname.toLowerCase();
      
      // Common search engine query parameters
      const queryParams: Record<string, string[]> = {
        'google': ['q', 'query', 'search'],
        'bing': ['q', 'query'],
        'yahoo': ['p', 'q'],
        'duckduckgo': ['q'],
        'baidu': ['wd', 'word'],
        'yandex': ['text', 'query']
      };
      
      for (const [engine, params] of Object.entries(queryParams)) {
        if (host.includes(engine)) {
          for (const param of params) {
            const value = url.searchParams.get(param);
            if (value) return decodeURIComponent(value);
          }
        }
      }
    } catch {
      // Invalid URL
    }
    
    return null;
  }

  /**
   * Find search engine
   */
  private findSearchEngine(
    params: Record<string, string>, 
    seReferrer: string | null,
    referrer: string | null
  ): string | null {
    // Check parameter
    if (params.se) {
      return decodeURIComponent(params.se);
    }
    
    // Extract from SE referrer
    if (seReferrer) {
      try {
        const url = new URL(seReferrer);
        return url.hostname;
      } catch {
        // Invalid URL
      }
    }
    
    // Extract from regular referrer
    if (referrer) {
      try {
        const url = new URL(referrer);
        return url.hostname;
      } catch {
        // Invalid URL
      }
    }
    
    return null;
  }

  /**
   * Find X-Requested-With header
   */
  private findXRequestedWith(request: Request): string | null {
    const value = request.headers.get('x-requested-with');
    return value || null;
  }

  /**
   * Find language
   */
  private findLanguage(request: Request): string | null {
    const acceptLanguage = request.headers.get('accept-language');
    if (!acceptLanguage) return null;
    
    const lang = acceptLanguage.split(',')[0].substring(0, 2).toUpperCase();
    return lang || null;
  }

  /**
   * Get IP address
   */
  private getIp(request: Request): string {
    const cfIp = request.headers.get('cf-connecting-ip');
    if (cfIp) return cfIp;
    
    const forwarded = request.headers.get('x-forwarded-for');
    if (forwarded) return forwarded.split(',')[0].trim();
    
    const realIp = request.headers.get('x-real-ip');
    if (realIp) return realIp;
    
    return 'unknown';
  }

  /**
   * Generate visitor code
   */
  private generateVisitorCode(): string {
    const timestamp = Date.now().toString(36);
    const random = Math.random().toString(36).substring(2, 10);
    return `${timestamp}${random}`;
  }

  /**
   * Generate session ID
   */
  private generateSessionId(): string {
    const timestamp = Date.now().toString(16);
    const random = Math.random().toString(16).substring(2, 14);
    return `sess_${timestamp}${random}`;
  }


  /**
   * Extract headers from request
   */
  private extractHeaders(request: Request): Record<string, string> {
    const headers: Record<string, string> = {};
    request.headers.forEach((value, key) => {
      headers[key.toLowerCase()] = value;
    });
    return headers;
  }

  /**
   * Resolve device info from user agent
   */
  private resolveDeviceInfo(userAgent: string): DeviceInfo {
    const result: DeviceInfo = {
      browser: 'Unknown',
      browserVersion: '',
      os: 'Unknown',
      osVersion: '',
      deviceType: 'desktop',
      deviceModel: '',
      deviceBrand: '',
      isMobile: false
    };

    if (!userAgent) return result;

    // Detect browser
    for (const [pattern, name] of BROWSER_PATTERNS) {
      const match = userAgent.match(pattern);
      if (match) {
        result.browser = name;
        result.browserVersion = match[1];
        break;
      }
    }

    // Detect OS
    for (const [pattern, name] of OS_PATTERNS) {
      const match = userAgent.match(pattern);
      if (match) {
        result.os = name;
        result.osVersion = match[1]?.replace('_', '.') || '';
        break;
      }
    }

    // Detect device type
    const isTablet = TABLET_PATTERNS.some(p => p.test(userAgent));
    const isMobile = MOBILE_PATTERNS.some(p => p.test(userAgent));
    
    if (isTablet) {
      result.deviceType = 'tablet';
      result.isMobile = true;
    } else if (isMobile) {
      result.deviceType = 'mobile';
      result.isMobile = true;
    } else {
      result.deviceType = 'desktop';
      result.isMobile = false;
    }

    // Detect device brand/model for mobile
    if (result.isMobile) {
      if (/iPhone/.test(userAgent)) {
        result.deviceBrand = 'Apple';
        result.deviceModel = 'iPhone';
      } else if (/iPad/.test(userAgent)) {
        result.deviceBrand = 'Apple';
        result.deviceModel = 'iPad';
      } else if (/Samsung/.test(userAgent)) {
        result.deviceBrand = 'Samsung';
        const modelMatch = userAgent.match(/Samsung[-\s]([A-Za-z0-9]+)/);
        result.deviceModel = modelMatch ? modelMatch[1] : '';
      }
    }

    return result;
  }
}
