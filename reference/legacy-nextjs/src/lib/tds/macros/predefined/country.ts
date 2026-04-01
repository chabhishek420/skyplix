/**
 * Country Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class CountryMacro implements MacroInterface {
  name = 'country';
  description = 'Country code (e.g., US, UK)';
  alwaysRaw = false;

  process(context: MacroContext, lang?: string): string | null {
    if (!context.rawClick?.country) return null;
    return context.rawClick.country;
  }
}
