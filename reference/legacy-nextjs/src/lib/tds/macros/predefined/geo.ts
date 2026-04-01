/**
 * Geo Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class CountryMacro implements MacroInterface {
  name = 'country';
  description = 'Country code (e.g., US, UK)';
  alwaysRaw = false;

  process(context: MacroContext, lang?: string): string | null {
    if (!context.rawClick?.country) return null;
    
    // For now just return country code
    // In Keitaro, it can also return country name in different languages
    return context.rawClick.country;
  }
}

export class CityMacro implements MacroInterface {
  name = 'city';
  description = 'City name';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.city) return null;
    return context.rawClick.city;
  }
}

export class RegionMacro implements MacroInterface {
  name = 'region';
  description = 'Region/State';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.region) return null;
    return context.rawClick.region;
  }
}
