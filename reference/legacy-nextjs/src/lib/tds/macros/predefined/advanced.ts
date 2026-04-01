/**
 * Advanced Macros
 * Sample, FromFile, and other special macros
 * Based on Keitaro's macro system
 */

import type { MacroInterface, MacroContext } from '../types';
import { promises as fs } from 'fs';
import path from 'path';
import crypto from 'crypto';

/**
 * Sample Macro
 * Returns sample value for testing
 */
export class SampleMacro implements MacroInterface {
  name = 'sample';
  description = 'Sample value for testing';
  alwaysRaw = false;
  
  process(context: MacroContext, type?: string): string | null {
    switch (type) {
      case 'ip':
        return '192.168.1.1';
      case 'country':
        return 'US';
      case 'city':
        return 'New York';
      case 'browser':
        return 'Chrome';
      case 'os':
        return 'Windows';
      case 'device':
        return 'desktop';
      case 'keyword':
        return 'sample keyword';
      case 'referrer':
        return 'https://google.com';
      case 'clickid':
        return 'a1b2c3d4e5f6g7h8i9j0k1l2';
      case 'datetime':
        return new Date().toISOString();
      default:
        return 'sample_value';
    }
  }
}

/**
 * From File Macro
 * Reads value from a file
 */
export class FromFileMacro implements MacroInterface {
  name = 'from_file';
  description = 'Read value from file';
  alwaysRaw = false;
  private cache: Map<string, { content: string; timestamp: number }> = new Map();
  private cacheTtl = 60000; // 1 minute cache
  
  async processAsync(context: MacroContext, filePath?: string): Promise<string | null> {
    if (!filePath) {
      return null;
    }
    
    try {
      // Check cache
      const cached = this.cache.get(filePath);
      if (cached && Date.now() - cached.timestamp < this.cacheTtl) {
        return cached.content;
      }
      
      // Resolve path (ensure within allowed directory)
      const basePath = process.env.MACRO_FILE_PATH || './data/macros';
      const resolvedPath = path.resolve(basePath, filePath);
      
      // Security check
      if (!resolvedPath.startsWith(path.resolve(basePath))) {
        console.warn('FromFile macro: path outside allowed directory');
        return null;
      }
      
      // Read file
      const content = await fs.readFile(resolvedPath, 'utf-8');
      
      // Cache result
      this.cache.set(filePath, { content: content.trim(), timestamp: Date.now() });
      
      return content.trim();
    } catch (error) {
      console.warn('FromFile macro error:', error);
      return null;
    }
  }
  
  process(context: MacroContext, filePath?: string): string | null {
    // Sync version - returns cached value or null
    if (!filePath) return null;
    
    const cached = this.cache.get(filePath);
    if (cached && Date.now() - cached.timestamp < this.cacheTtl) {
      return cached.content;
    }
    
    return null;
  }
}

/**
 * Base64 Encode Macro
 */
export class Base64EncodeMacro implements MacroInterface {
  name = 'base64_encode';
  description = 'Base64 encode value';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    if (!value) return null;
    return Buffer.from(value).toString('base64');
  }
}

/**
 * Base64 Decode Macro
 */
export class Base64DecodeMacro implements MacroInterface {
  name = 'base64_decode';
  description = 'Base64 decode value';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    if (!value) return null;
    try {
      return Buffer.from(value, 'base64').toString('utf-8');
    } catch {
      return null;
    }
  }
}

/**
 * URLEncode Macro
 */
export class UrlEncodeMacro implements MacroInterface {
  name = 'urlencode';
  description = 'URL encode value';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    if (!value) return null;
    return encodeURIComponent(value);
  }
}

/**
 * URLDecode Macro
 */
export class UrlDecodeMacro implements MacroInterface {
  name = 'urldecode';
  description = 'URL decode value';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    if (!value) return null;
    try {
      return decodeURIComponent(value);
    } catch {
      return value;
    }
  }
}

/**
 * MD5 Macro
 */
export class Md5Macro implements MacroInterface {
  name = 'md5';
  description = 'MD5 hash value';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    if (!value) return null;
    return crypto.createHash('md5').update(value).digest('hex');
  }
}

/**
 * SHA256 Macro
 */
export class Sha256Macro implements MacroInterface {
  name = 'sha256';
  description = 'SHA256 hash value';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    if (!value) return null;
    return crypto.createHash('sha256').update(value).digest('hex');
  }
}

/**
 * Lowercase Macro
 */
export class LowercaseMacro implements MacroInterface {
  name = 'lower';
  description = 'Convert to lowercase';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    return value?.toLowerCase() || null;
  }
}

/**
 * Uppercase Macro
 */
export class UppercaseMacro implements MacroInterface {
  name = 'upper';
  description = 'Convert to uppercase';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string): string | null {
    return value?.toUpperCase() || null;
  }
}

/**
 * Substring Macro
 */
export class SubstringMacro implements MacroInterface {
  name = 'substr';
  description = 'Get substring';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string, start?: string, length?: string): string | null {
    if (!value) return null;
    const startIndex = parseInt(start || '0', 10);
    const len = length ? parseInt(length, 10) : undefined;
    
    return len ? value.substring(startIndex, startIndex + len) : value.substring(startIndex);
  }
}

/**
 * Replace Macro
 */
export class ReplaceMacro implements MacroInterface {
  name = 'replace';
  description = 'Replace substring';
  alwaysRaw = false;
  
  process(context: MacroContext, value?: string, search?: string, replace?: string): string | null {
    if (!value || !search) return null;
    return value.split(search).join(replace || '');
  }
}
