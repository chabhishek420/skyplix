/**
 * Sub ID / Click ID Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class SubidMacro implements MacroInterface {
  name = 'subid';
  description = 'Sub ID / Click ID';
  alwaysRaw = false;

  process(context: MacroContext, xSeparator?: string): string | null {
    if (!context.rawClick) return null;
    let subid = context.rawClick.subId || '';
    
    if (xSeparator === 'true' || xSeparator === '1') {
      subid = subid.replace(/-/g, 'x');
    }
    
    return subid;
  }
}

export class SubIdMacro implements MacroInterface {
  name = 'sub_id';
  description = 'Sub ID (alias)';
  alwaysRaw = false;

  process(context: MacroContext, num?: string): string | null {
    if (!context.rawClick) return null;
    
    if (num) {
      const n = parseInt(num, 10);
      if (n >= 1 && n <= 15) {
        const key = `subId${n}` as keyof typeof context.rawClick;
        return (context.rawClick[key] as string) || '';
      }
    }
    
    return context.rawClick.subId || '';
  }
}

export class ClickIdMacro implements MacroInterface {
  name = 'clickid';
  description = 'Click ID (same as sub_id)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    return context.rawClick.clickId || context.rawClick.subId || '';
  }
}
