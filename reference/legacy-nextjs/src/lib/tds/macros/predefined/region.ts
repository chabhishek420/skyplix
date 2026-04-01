/**
 * Region Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class RegionMacro implements MacroInterface {
  name = 'region';
  description = 'Region/State';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.region) return null;
    return context.rawClick.region;
  }
}
