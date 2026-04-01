/**
 * Cost, Random, and other utility macros
 */

import type { MacroInterface, MacroContext } from '../types';
import { randomBytes } from 'crypto';

export class CostMacro implements MacroInterface {
  name = 'cost';
  description = 'Click cost';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.cost) return '0';
    return String(context.rawClick.cost);
  }
}

export class RandomMacro implements MacroInterface {
  name = 'random';
  description = 'Random string';
  alwaysRaw = false;

  process(context: MacroContext, length?: string): string | null {
    const len = length ? parseInt(length, 10) : 8;
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    
    // Use crypto random if available
    const bytes = randomBytes(len);
    for (let i = 0; i < len; i++) {
      result += chars[bytes[i] % chars.length];
    }
    
    return result;
  }
}
