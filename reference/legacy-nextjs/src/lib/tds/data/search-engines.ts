/**
 * Search Engine Referrer Dictionary (Self-Contained TypeScript Data)
 * 
 * Search engine patterns for extracting keywords from referrer URLs.
 * This is a standalone TypeScript implementation that requires NO PHP dependencies.
 * 
 * Data originally derived from Keitaro TDS reference implementation.
 */

export interface SearchEngineInfo {
  host: string;      // Hostname pattern to match
  var: string;       // Query parameter name for keywords
  charset: string;   // Character encoding
}

export const SEARCH_ENGINES: SearchEngineInfo[] = [
  { host: 'yandex.', var: 'text', charset: 'UTF-8' },
  { host: 'google.', var: 'q', charset: 'UTF-8' },
  { host: 'rambler.ru', var: 'words', charset: 'UTF-8' },
  { host: 'sm.aport.ru', var: 'r', charset: 'windows-1251' },
  { host: 'search.yahoo.com', var: 'p', charset: 'UTF-8' },
  { host: 'search.live.com', var: 'q', charset: 'UTF-8' },
  { host: 'search.bigmir.net', var: 'q', charset: 'windows-1251' },
  { host: 'go.mail.ru', var: 'q', charset: 'UTF-8' },
  { host: 'livetool.ru', var: 'text', charset: 'UTF-8' },
  { host: 'bing.com', var: 'q', charset: 'UTF-8' },
  { host: 'sputnik.ru', var: 'q', charset: 'UTF-8' },
  // Additional search engines
  { host: 'duckduckgo.com', var: 'q', charset: 'UTF-8' },
  { host: 'baidu.com', var: 'wd', charset: 'UTF-8' },
  { host: 'ask.com', var: 'q', charset: 'UTF-8' },
  { host: 'aol.com', var: 'q', charset: 'UTF-8' },
];

/**
 * Parse keyword from referrer URL
 */
export function parseKeywordFromReferrer(referrer: string): string | null {
  if (!referrer) return null;
  
  try {
    const url = new URL(referrer);
    const host = url.hostname.toLowerCase();
    
    for (const engine of SEARCH_ENGINES) {
      if (host.includes(engine.host.replace('.', '')) || host.endsWith(engine.host) || host === engine.host.replace('.', '')) {
        const keyword = url.searchParams.get(engine.var);
        if (keyword) {
          // Handle charset conversion if needed
          if (engine.charset !== 'UTF-8') {
            try {
              // In Node.js, we'd need iconv-lite, but for now return as-is
              // since most modern search engines use UTF-8
              return decodeURIComponent(keyword);
            } catch {
              return keyword;
            }
          }
          return decodeURIComponent(keyword);
        }
      }
    }
  } catch {
    // Invalid URL
  }
  
  return null;
}

/**
 * Get search engine name from referrer
 */
export function getSearchEngineFromReferrer(referrer: string): string | null {
  if (!referrer) return null;
  
  try {
    const url = new URL(referrer);
    return url.hostname;
  } catch {
    return null;
  }
}

/**
 * Check if referrer is from a known search engine
 */
export function isSearchEngineReferrer(referrer: string): boolean {
  if (!referrer) return false;
  
  try {
    const url = new URL(referrer);
    const host = url.hostname.toLowerCase();
    
    return SEARCH_ENGINES.some(engine => 
      host.includes(engine.host.replace('.', '')) || 
      host.endsWith(engine.host) ||
      host === engine.host.replace('.', '')
    );
  } catch {
    return false;
  }
}
