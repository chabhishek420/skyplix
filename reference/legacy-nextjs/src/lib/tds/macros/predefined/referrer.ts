/**
 * Referrer Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class ReferrerMacro implements MacroInterface {
  name = 'referrer';
  description = 'Referrer URL';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.referrer) return null;
    return context.rawClick.referrer;
  }
}
