/**
 * Bot Signatures Dictionary (Self-Contained TypeScript Data)
 * 
 * Known bot/crawler user agent signatures for detection.
 * This is a standalone TypeScript implementation that requires NO PHP dependencies.
 * 
 * Data originally derived from Keitaro TDS reference implementation.
 */

export const BOT_SIGNATURES: string[] = [
  // Search engine bots
  'Advisorbot',
  'crawler',
  'oBot',
  'spider',
  'ezooms',
  'FlipboardProxy',
  'CHTML Proxy',
  'TweetmemeBot',
  'bitlybot',
  'SputnikBot',
  'Googlebot',
  'SemrushBot',
  'YandexBot',
  'WebIndex',
  'Slurp',
  'org_bot',
  'bot.html',
  'bot.php',
  'Twitterbot',
  'Adsbot',
  '/bots',
  'RU_Bot',
  'OrangeBot',
  'Synapse',
  'SEOstats',
  'urllib',
  'Owler',
  'ltx71',
  'WinHttpRequest',
  'python-requests',
  'PageAnalyzer',
  'OpenLinkProfiler',
  'BOT for JCE',
  'BUbiNG',
  'Nutch',
  'megaindex',
  'SeznamBot',
  'Twitterbot',
  'bingbot',
  'facebook',
  'Google Web Preview',
  'BingPreview/1.0b',
  'Exabot-Thumbnails',
  'coccoc',
  'Googlebot',
  'Sleuth',
  'cmcm.com',
  'YandexMobileBot',
  'curl',
  'Google-Youtube-Links',
  'MailRuConnect',
  'vkShare',
  'SurveyBot',
  'AppEngine',
  'NetcraftSurveyAgent'
];

// Additional bot patterns from TypeScript bot-detection.ts
export const ADDITIONAL_BOT_PATTERNS: string[] = [
  // Search engines
  'googlebot',
  'bingbot',
  'slurp',
  'duckduckbot',
  'baiduspider',
  'yandexbot',
  'sogou',
  'exabot',
  'facebot',
  'ia_archiver',
  
  // SEO tools
  'ahrefsbot',
  'semrushbot',
  'mj12bot',
  'dotbot',
  'blexbot',
  'linkdex',
  'majestic',
  'rogerbot',
  'screaming',
  'seo',
  
  // Security scanners
  'nikto',
  'sqlmap',
  'nmap',
  'masscan',
  'zgrab',
  'gobuster',
  'dirbuster',
  'wfuzz',
  'burp',
  'owasp',
  'acunetix',
  'nessus',
  'qualys',
  
  // Monitoring tools
  'pingdom',
  'newrelic',
  'datadog',
  'statuscake',
  'uptimerobot',
  'monitor',
  
  // HTTP libraries
  'python-requests',
  'python-urllib',
  'curl',
  'wget',
  'httpclient',
  'libwww',
  'lwp-trivial',
  'java/',
  'okhttp',
  'axios',
  'got/',
  'superagent',
  'node-fetch',
  'undici',
  'bun',
  'deno',
  
  // Headless browsers
  'headless',
  'phantomjs',
  'selenium',
  'webdriver',
  'puppeteer',
  'playwright',
  'chromium',
  'chrome-lighthouse',
  
  // Scrapers
  'scraper',
  'crawler',
  'spider',
  'harvest',
  'extract',
  'bot',
  'crawl'
];

/**
 * Get all bot signatures (deduplicated)
 */
export function getAllBotSignatures(): string[] {
  const combined = [...BOT_SIGNATURES, ...ADDITIONAL_BOT_PATTERNS];
  const seen = new Set<string>();
  return combined.filter(sig => {
    const lower = sig.toLowerCase();
    if (seen.has(lower)) return false;
    seen.add(lower);
    return true;
  });
}

/**
 * Check if user agent matches any bot signature
 */
export function isBotUserAgent(userAgent: string): boolean {
  if (!userAgent) return true; // Empty UA is suspicious
  
  const lowerUA = userAgent.toLowerCase();
  
  // Check all signatures
  const allSignatures = getAllBotSignatures();
  return allSignatures.some(sig => lowerUA.includes(sig.toLowerCase()));
}

/**
 * Get matched bot signature
 */
export function getMatchedBotSignature(userAgent: string): string | null {
  if (!userAgent) return 'empty-user-agent';
  
  const lowerUA = userAgent.toLowerCase();
  const allSignatures = getAllBotSignatures();
  
  for (const sig of allSignatures) {
    if (lowerUA.includes(sig.toLowerCase())) {
      return sig;
    }
  }
  
  return null;
}
