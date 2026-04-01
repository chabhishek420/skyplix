/**
 * Bot Detection & Cloaking System
 * 
 * Detects bots, crawlers, and suspicious traffic to serve safe pages
 * while directing legitimate traffic to real destinations.
 * 
 * Detection methods:
 * 1. User-Agent analysis (known bot patterns)
 * 2. Header analysis (automation indicators)
 * 3. IP analysis (datacenter ranges, proxies)
 * 4. Referer analysis (suspicious referrer patterns)
 * 5. Parameter analysis (debug flags)
 * 6. Database BotRule matching (custom rules)
 */

import { db } from '@/lib/db';
import { getAllBotSignatures } from './data/bot-signatures';

// Comprehensive bot signatures from data dictionary (140+ patterns)
// Includes: search engines, SEO tools, security scanners, monitoring tools,
// HTTP libraries, headless browsers, and scrapers
const BOT_USER_AGENTS = getAllBotSignatures();

// Debug parameters that trigger safe page
const DEBUG_PARAMS = ['debug', 'test', 'dev', 'admin', 'dbg', 'trace', 'inspect'];

// Suspicious referrer patterns (bots faking referrers)
const SUSPICIOUS_REFERRER_PATTERNS = [
  // Empty or null referrer from direct bot access
  { pattern: /^$/, name: 'Empty referrer' },
  // Known bot domains
  { pattern: /semrush\.com/i, name: 'SEMrush' },
  { pattern: /ahrefs\.com/i, name: 'Ahrefs' },
  { pattern: /majestic\.com/i, name: 'Majestic' },
  { pattern: /moz\.com/i, name: 'Moz' },
  { pattern: /screamingfrog\.co\.uk/i, name: 'Screaming Frog' },
  // URL inspection tools
  { pattern: /developers\.google\.com\/speed/i, name: 'Google PageSpeed' },
  { pattern: /gtmetrix\.com/i, name: 'GTmetrix' },
  { pattern: /webpagetest\.org/i, name: 'WebPageTest' },
];

// Known datacenter IP ranges (AWS, GCP, Azure, DigitalOcean, etc.)
// These are commonly used by bots and scrapers
const DATACENTER_IP_RANGES = [
  // Amazon AWS (major ranges)
  { prefix: '3.', name: 'AWS' },
  { prefix: '13.', name: 'AWS' },
  { prefix: '15.', name: 'AWS' },
  { prefix: '16.', name: 'AWS' },
  { prefix: '18.', name: 'AWS' },
  { prefix: '23.', name: 'AWS' },
  { prefix: '35.', name: 'AWS' },
  { prefix: '52.', name: 'AWS' },
  { prefix: '54.', name: 'AWS' },
  { prefix: '63.', name: 'AWS' },
  { prefix: '67.', name: 'AWS' },
  { prefix: '72.', name: 'AWS' },
  { prefix: '75.', name: 'AWS' },
  { prefix: '79.', name: 'AWS' },
  { prefix: '87.', name: 'AWS' },
  { prefix: '99.', name: 'AWS' },
  { prefix: '100.', name: 'AWS' },
  { prefix: '107.', name: 'AWS' },
  { prefix: '108.', name: 'AWS' },
  { prefix: '174.', name: 'AWS' },
  { prefix: '176.', name: 'AWS' },
  { prefix: '184.', name: 'AWS' },
  { prefix: '204.', name: 'AWS' },
  { prefix: '205.', name: 'AWS' },
  { prefix: '216.', name: 'AWS' },
  
  // Google Cloud Platform
  { prefix: '8.', name: 'GCP' },
  { prefix: '34.', name: 'GCP' },
  { prefix: '35.', name: 'GCP' },
  { prefix: '104.', name: 'GCP' },
  { prefix: '107.', name: 'GCP' },
  { prefix: '130.', name: 'GCP' },
  { prefix: '146.', name: 'GCP' },
  { prefix: '162.', name: 'GCP' },
  { prefix: '173.', name: 'GCP' },
  { prefix: '199.', name: 'GCP' },
  
  // Microsoft Azure
  { prefix: '4.', name: 'Azure' },
  { prefix: '13.', name: 'Azure' },
  { prefix: '20.', name: 'Azure' },
  { prefix: '23.', name: 'Azure' },
  { prefix: '40.', name: 'Azure' },
  { prefix: '51.', name: 'Azure' },
  { prefix: '52.', name: 'Azure' },
  { prefix: '65.', name: 'Azure' },
  { prefix: '70.', name: 'Azure' },
  { prefix: '74.', name: 'Azure' },
  { prefix: '98.', name: 'Azure' },
  { prefix: '104.', name: 'Azure' },
  { prefix: '111.', name: 'Azure' },
  { prefix: '137.', name: 'Azure' },
  { prefix: '168.', name: 'Azure' },
  { prefix: '191.', name: 'Azure' },
  
  // DigitalOcean
  { prefix: '45.', name: 'DigitalOcean' },
  { prefix: '46.', name: 'DigitalOcean' },
  { prefix: '64.', name: 'DigitalOcean' },
  { prefix: '67.', name: 'DigitalOcean' },
  { prefix: '68.', name: 'DigitalOcean' },
  { prefix: '80.', name: 'DigitalOcean' },
  { prefix: '82.', name: 'DigitalOcean' },
  { prefix: '104.', name: 'DigitalOcean' },
  { prefix: '128.', name: 'DigitalOcean' },
  { prefix: '134.', name: 'DigitalOcean' },
  { prefix: '137.', name: 'DigitalOcean' },
  { prefix: '138.', name: 'DigitalOcean' },
  { prefix: '139.', name: 'DigitalOcean' },
  { prefix: '142.', name: 'DigitalOcean' },
  { prefix: '143.', name: 'DigitalOcean' },
  { prefix: '144.', name: 'DigitalOcean' },
  { prefix: '159.', name: 'DigitalOcean' },
  { prefix: '161.', name: 'DigitalOcean' },
  { prefix: '162.', name: 'DigitalOcean' },
  { prefix: '164.', name: 'DigitalOcean' },
  { prefix: '165.', name: 'DigitalOcean' },
  { prefix: '167.', name: 'DigitalOcean' },
  { prefix: '168.', name: 'DigitalOcean' },
  { prefix: '170.', name: 'DigitalOcean' },
  { prefix: '174.', name: 'DigitalOcean' },
  { prefix: '178.', name: 'DigitalOcean' },
  { prefix: '188.', name: 'DigitalOcean' },
  { prefix: '192.', name: 'DigitalOcean' },
  { prefix: '198.', name: 'DigitalOcean' },
  { prefix: '206.', name: 'DigitalOcean' },
  { prefix: '207.', name: 'DigitalOcean' },
  { prefix: '209.', name: 'DigitalOcean' },
  
  // Hetzner
  { prefix: '5.', name: 'Hetzner' },
  { prefix: '46.', name: 'Hetzner' },
  { prefix: '49.', name: 'Hetzner' },
  { prefix: '78.', name: 'Hetzner' },
  { prefix: '79.', name: 'Hetzner' },
  { prefix: '85.', name: 'Hetzner' },
  { prefix: '88.', name: 'Hetzner' },
  { prefix: '91.', name: 'Hetzner' },
  { prefix: '94.', name: 'Hetzner' },
  { prefix: '95.', name: 'Hetzner' },
  { prefix: '116.', name: 'Hetzner' },
  { prefix: '128.', name: 'Hetzner' },
  { prefix: '129.', name: 'Hetzner' },
  { prefix: '136.', name: 'Hetzner' },
  { prefix: '138.', name: 'Hetzner' },
  { prefix: '142.', name: 'Hetzner' },
  { prefix: '144.', name: 'Hetzner' },
  { prefix: '148.', name: 'Hetzner' },
  { prefix: '159.', name: 'Hetzner' },
  { prefix: '162.', name: 'Hetzner' },
  { prefix: '167.', name: 'Hetzner' },
  { prefix: '168.', name: 'Hetzner' },
  { prefix: '176.', name: 'Hetzner' },
  { prefix: '178.', name: 'Hetzner' },
  { prefix: '188.', name: 'Hetzner' },
  { prefix: '195.', name: 'Hetzner' },
  { prefix: '213.', name: 'Hetzner' },
  { prefix: '233.', name: 'Hetzner' },
  
  // OVH
  { prefix: '5.', name: 'OVH' },
  { prefix: '37.', name: 'OVH' },
  { prefix: '46.', name: 'OVH' },
  { prefix: '51.', name: 'OVH' },
  { prefix: '54.', name: 'OVH' },
  { prefix: '77.', name: 'OVH' },
  { prefix: '79.', name: 'OVH' },
  { prefix: '87.', name: 'OVH' },
  { prefix: '91.', name: 'OVH' },
  { prefix: '92.', name: 'OVH' },
  { prefix: '93.', name: 'OVH' },
  { prefix: '94.', name: 'OVH' },
  { prefix: '104.', name: 'OVH' },
  { prefix: '137.', name: 'OVH' },
  { prefix: '145.', name: 'OVH' },
  { prefix: '146.', name: 'OVH' },
  { prefix: '148.', name: 'OVH' },
  { prefix: '149.', name: 'OVH' },
  { prefix: '150.', name: 'OVH' },
  { prefix: '151.', name: 'OVH' },
  { prefix: '164.', name: 'OVH' },
  { prefix: '167.', name: 'OVH' },
  { prefix: '176.', name: 'OVH' },
  { prefix: '178.', name: 'OVH' },
  { prefix: '185.', name: 'OVH' },
  { prefix: '188.', name: 'OVH' },
  { prefix: '192.', name: 'OVH' },
  { prefix: '193.', name: 'OVH' },
  { prefix: '195.', name: 'OVH' },
  { prefix: '198.', name: 'OVH' },
  { prefix: '200.', name: 'OVH' },
  { prefix: '213.', name: 'OVH' },
  
  // Linode
  { prefix: '23.', name: 'Linode' },
  { prefix: '45.', name: 'Linode' },
  { prefix: '50.', name: 'Linode' },
  { prefix: '66.', name: 'Linode' },
  { prefix: '72.', name: 'Linode' },
  { prefix: '96.', name: 'Linode' },
  { prefix: '97.', name: 'Linode' },
  { prefix: '103.', name: 'Linode' },
  { prefix: '106.', name: 'Linode' },
  { prefix: '139.', name: 'Linode' },
  { prefix: '170.', name: 'Linode' },
  { prefix: '173.', name: 'Linode' },
  { prefix: '176.', name: 'Linode' },
  { prefix: '192.', name: 'Linode' },
  { prefix: '198.', name: 'Linode' },
  { prefix: '203.', name: 'Linode' },
  { prefix: '205.', name: 'Linode' },
  { prefix: '209.', name: 'Linode' },
  { prefix: '213.', name: 'Linode' },
  
  // Cloudflare (often used for bot protection but can indicate proxy)
  { prefix: '104.', name: 'Cloudflare' },
  { prefix: '108.', name: 'Cloudflare' },
  { prefix: '131.', name: 'Cloudflare' },
  { prefix: '162.', name: 'Cloudflare' },
  { prefix: '172.', name: 'Cloudflare' },
  { prefix: '188.', name: 'Cloudflare' },
  { prefix: '198.', name: 'Cloudflare' },
  
  // Known VPN/Proxy services
  { prefix: '45.', name: 'VPN/Proxy' },
  { prefix: '77.', name: 'VPN/Proxy' },
  { prefix: '89.', name: 'VPN/Proxy' },
  { prefix: '185.', name: 'VPN/Proxy' },
  { prefix: '193.', name: 'VPN/Proxy' },
];

// Cache for database bot rules
let botRulesCache: BotRule[] = [];
let botRulesCacheTime = 0;
const BOT_RULES_CACHE_TTL = 5 * 60 * 1000; // 5 minutes

export interface BotDetectionResult {
  isBot: boolean;
  reason: string | null;
  confidence: number; // 0-100
  botType: 'crawler' | 'scanner' | 'tool' | 'suspicious' | 'debug' | 'datacenter' | null;
}

export interface DetectionContext {
  userAgent: string | null;
  ip: string | null;
  referrer: string | null;
  headers: Record<string, string>;
  params: Record<string, string>;
  cookies: Record<string, string>;
}

interface BotRule {
  id: string;
  name: string;
  type: string;
  pattern: string;
  action: string;
  priority: number;
}

/**
 * Detect if request is from a bot
 */
export async function detectBot(context: DetectionContext): Promise<BotDetectionResult> {
  // Check debug parameters first (highest confidence)
  const debugResult = checkDebugParams(context.params);
  if (debugResult.isBot) return debugResult;
  
  // Check user agent
  const uaResult = checkUserAgent(context.userAgent);
  if (uaResult.isBot) return uaResult;
  
  // Check for suspicious headers
  const headerResult = checkHeaders(context.headers);
  if (headerResult.isBot) return headerResult;
  
  // Check for missing typical browser headers
  const missingResult = checkMissingBrowserHeaders(context);
  if (missingResult.isBot) return missingResult;
  
  // Check referer patterns
  const refererResult = checkReferer(context.referrer);
  if (refererResult.isBot) return refererResult;
  
  // Check IP patterns (datacenter ranges)
  const ipResult = checkIpPatterns(context.ip);
  if (ipResult.isBot) return ipResult;
  
  // Check database bot rules
  const ruleResult = await checkDatabaseRules(context);
  if (ruleResult.isBot) return ruleResult;
  
  // Not a bot
  return {
    isBot: false,
    reason: null,
    confidence: 0,
    botType: null
  };
}

/**
 * Synchronous version for backward compatibility
 */
export function detectBotSync(context: DetectionContext): BotDetectionResult {
  // Check debug parameters first (highest confidence)
  const debugResult = checkDebugParams(context.params);
  if (debugResult.isBot) return debugResult;
  
  // Check user agent
  const uaResult = checkUserAgent(context.userAgent);
  if (uaResult.isBot) return uaResult;
  
  // Check for suspicious headers
  const headerResult = checkHeaders(context.headers);
  if (headerResult.isBot) return headerResult;
  
  // Check for missing typical browser headers
  const missingResult = checkMissingBrowserHeaders(context);
  if (missingResult.isBot) return missingResult;
  
  // Check referer patterns
  const refererResult = checkReferer(context.referrer);
  if (refererResult.isBot) return refererResult;
  
  // Check IP patterns (datacenter ranges)
  const ipResult = checkIpPatterns(context.ip);
  if (ipResult.isBot) return ipResult;
  
  // Not a bot
  return {
    isBot: false,
    reason: null,
    confidence: 0,
    botType: null
  };
}

/**
 * Check for debug parameters
 */
function checkDebugParams(params: Record<string, string>): BotDetectionResult {
  for (const [key, value] of Object.entries(params)) {
    const lowerKey = key.toLowerCase();
    if (DEBUG_PARAMS.includes(lowerKey)) {
      return {
        isBot: true,
        reason: `Debug parameter detected: ${key}=${value}`,
        confidence: 95,
        botType: 'debug'
      };
    }
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check user agent for bot signatures
 */
function checkUserAgent(userAgent: string | null): BotDetectionResult {
  if (!userAgent) {
    return {
      isBot: true,
      reason: 'Missing User-Agent header',
      confidence: 80,
      botType: 'tool'
    };
  }
  
  const lowerUa = userAgent.toLowerCase();
  
  // Check for known bot patterns
  for (const botPattern of BOT_USER_AGENTS) {
    if (lowerUa.includes(botPattern)) {
      return {
        isBot: true,
        reason: `Bot user agent detected: ${botPattern}`,
        confidence: 90,
        botType: lowerUa.includes('scraper') || lowerUa.includes('crawler') 
          ? 'crawler' 
          : lowerUa.includes('nikto') || lowerUa.includes('sqlmap')
            ? 'scanner'
            : 'tool'
      };
    }
  }
  
  // Check for headless browser indicators
  if (lowerUa.includes('headless') || lowerUa.includes('selenium') || 
      lowerUa.includes('webdriver') || lowerUa.includes('puppeteer')) {
    return {
      isBot: true,
      reason: 'Headless browser detected',
      confidence: 95,
      botType: 'tool'
    };
  }
  
  // Check for very short or suspicious user agents
  if (userAgent.length < 20) {
    return {
      isBot: true,
      reason: 'Suspiciously short User-Agent',
      confidence: 60,
      botType: 'suspicious'
    };
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check for suspicious headers
 */
function checkHeaders(headers: Record<string, string>): BotDetectionResult {
  // Check for automation tool headers
  const automationHeaders = ['x-selenium', 'x-puppeteer', 'x-playwright', 'x-automation'];
  for (const header of automationHeaders) {
    if (headers[header] || headers[header.toLowerCase()]) {
      return {
        isBot: true,
        reason: `Automation header detected: ${header}`,
        confidence: 95,
        botType: 'tool'
      };
    }
  }
  
  // Check for exposed backend headers (GCP, etc.)
  const viaHeader = headers['via'] || headers['Via'];
  if (viaHeader && viaHeader.toLowerCase().includes('google')) {
    return {
      isBot: true,
      reason: 'Backend infrastructure exposed via Via header',
      confidence: 50,
      botType: 'suspicious'
    };
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check for missing typical browser headers
 */
function checkMissingBrowserHeaders(context: DetectionContext): BotDetectionResult {
  const typicalHeaders = ['accept', 'accept-language', 'accept-encoding'];
  const missing: string[] = [];
  
  for (const header of typicalHeaders) {
    if (!context.headers[header] && !context.headers[header.toLowerCase()]) {
      missing.push(header);
    }
  }
  
  if (missing.length >= 2) {
    return {
      isBot: true,
      reason: `Missing browser headers: ${missing.join(', ')}`,
      confidence: 70,
      botType: 'tool'
    };
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check referer for suspicious patterns
 */
function checkReferer(referrer: string | null): BotDetectionResult {
  if (!referrer) {
    // No referrer is common but worth noting
    return { isBot: false, reason: null, confidence: 0, botType: null };
  }
  
  // Check for suspicious referrer patterns
  for (const { pattern, name } of SUSPICIOUS_REFERRER_PATTERNS) {
    if (pattern.test(referrer)) {
      return {
        isBot: true,
        reason: `Suspicious referrer: ${name}`,
        confidence: 75,
        botType: 'crawler'
      };
    }
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check IP patterns (data center ranges, known proxies, etc.)
 */
function checkIpPatterns(ip: string | null): BotDetectionResult {
  if (!ip) {
    return { isBot: false, reason: null, confidence: 0, botType: null };
  }
  
  // Skip private/reserved IPs (internal traffic)
  const privatePrefixes = [
    '10.',
    '172.16.', '172.17.', '172.18.', '172.19.', '172.20.', '172.21.', 
    '172.22.', '172.23.', '172.24.', '172.25.', '172.26.', '172.27.',
    '172.28.', '172.29.', '172.30.', '172.31.',
    '192.168.',
    '127.',
    '169.254.', // Link-local
    'fc', 'fd', 'fe80', // IPv6 private
    '::1', // IPv6 localhost
  ];
  
  for (const prefix of privatePrefixes) {
    if (ip.toLowerCase().startsWith(prefix.toLowerCase())) {
      // Private IPs are not bots, skip further checks
      return { isBot: false, reason: null, confidence: 0, botType: null };
    }
  }
  
  // Check for known datacenter IP ranges
  for (const { prefix, name } of DATACENTER_IP_RANGES) {
    if (ip.startsWith(prefix)) {
      return {
        isBot: true,
        reason: `Datacenter IP detected: ${name}`,
        confidence: 65,
        botType: 'datacenter'
      };
    }
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check database bot rules
 */
async function checkDatabaseRules(context: DetectionContext): Promise<BotDetectionResult> {
  try {
    // Refresh cache if needed
    if (Date.now() - botRulesCacheTime > BOT_RULES_CACHE_TTL) {
      await refreshBotRulesCache();
    }
    
    // Check each rule against context
    for (const rule of botRulesCache) {
      const match = await checkRuleMatch(rule, context);
      if (match) {
        return {
          isBot: true,
          reason: `Bot rule matched: ${rule.name}`,
          confidence: 85,
          botType: 'suspicious'
        };
      }
    }
  } catch (error) {
    // Log error but don't fail detection
    console.error('Error checking database bot rules:', error);
  }
  
  return { isBot: false, reason: null, confidence: 0, botType: null };
}

/**
 * Check if a rule matches the context
 */
async function checkRuleMatch(rule: BotRule, context: DetectionContext): Promise<boolean> {
  const pattern = rule.pattern.toLowerCase();
  
  switch (rule.type) {
    case 'user_agent':
      return context.userAgent?.toLowerCase().includes(pattern) ?? false;
    
    case 'ip':
      return context.ip === pattern || (context.ip?.startsWith(pattern) ?? false);
    
    case 'referrer':
      return context.referrer?.toLowerCase().includes(pattern) ?? false;
    
    case 'header':
      // Format: "header_name:value"
      const [headerName, headerValue] = pattern.split(':');
      if (headerName && headerValue) {
        const header = context.headers[headerName] || context.headers[headerName.toLowerCase()];
        return header?.toLowerCase().includes(headerValue) ?? false;
      }
      return false;
    
    case 'country':
      // Would need geo resolution - skip for now
      return false;
    
    default:
      return false;
  }
}

/**
 * Refresh bot rules cache from database
 */
async function refreshBotRulesCache(): Promise<void> {
  try {
    const rules = await db.botRule.findMany({
      where: { status: 'active' },
      orderBy: { priority: 'desc' }
    });
    
    botRulesCache = rules.map(rule => ({
      id: rule.id,
      name: rule.name,
      type: rule.type,
      pattern: rule.pattern,
      action: rule.action,
      priority: rule.priority
    }));
    
    botRulesCacheTime = Date.now();
  } catch (error) {
    console.error('Error refreshing bot rules cache:', error);
    // Keep existing cache on error
  }
}

/**
 * Clear bot rules cache (for testing or manual refresh)
 */
export function clearBotRulesCache(): void {
  botRulesCache = [];
  botRulesCacheTime = 0;
}

/**
 * Get safe page URL based on detection
 */
export function getSafePageUrl(
  detection: BotDetectionResult, 
  language: string = 'en'
): string {
  // In production, these would come from database/configuration
  const safePages: Record<string, string> = {
    'debug': `/safe/debug`,
    'crawler': `/safe/bot`,
    'scanner': `/safe/security`,
    'tool': `/safe/error`,
    'suspicious': `/safe/verify`,
    'datacenter': `/safe/verify`,
  };
  
  const basePath = safePages[detection.botType || 'suspicious'] || '/safe';
  
  // Add language parameter
  return `${basePath}?lang=${language}`;
}

/**
 * Check if request should be cloaked
 */
export async function shouldCloak(context: DetectionContext): Promise<boolean> {
  const result = await detectBot(context);
  return result.isBot && result.confidence >= 70;
}

/**
 * Synchronous version for backward compatibility
 */
export function shouldCloakSync(context: DetectionContext): boolean {
  const result = detectBotSync(context);
  return result.isBot && result.confidence >= 70;
}
