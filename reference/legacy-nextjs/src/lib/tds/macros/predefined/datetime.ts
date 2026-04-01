/**
 * DateTime Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class DateMacro implements MacroInterface {
  name = 'date';
  description = 'Current date (YYYY-MM-DD)';
  alwaysRaw = false;

  process(context: MacroContext, format?: string): string | null {
    const now = context.rawClick?.datetime || new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    
    if (format === 'ymd') return `${year}${month}${day}`;
    return `${year}-${month}-${day}`;
  }
}

export class TimeMacro implements MacroInterface {
  name = 'time';
  description = 'Current time (HH:MM:SS)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    const now = context.rawClick?.datetime || new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
  }
}

export class TimestampMacro implements MacroInterface {
  name = 'timestamp';
  description = 'Unix timestamp';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    const now = context.rawClick?.datetime || new Date();
    return String(Math.floor(now.getTime() / 1000));
  }
}
