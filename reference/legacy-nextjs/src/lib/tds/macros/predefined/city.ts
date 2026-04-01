/**
 * City Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class CityMacro implements MacroInterface {
  name = 'city';
  description = 'City name';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.city) return null;
    return context.rawClick.city;
  }
}
