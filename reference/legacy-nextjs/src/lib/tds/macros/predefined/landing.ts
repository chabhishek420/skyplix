/**
 * Landing Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class LandingMacro implements MacroInterface {
  name = 'landing';
  description = 'Landing page URL';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (context.landing?.url) {
      return context.landing.url;
    }
    if (context.rawClick?.landingUrl) {
      return context.rawClick.landingUrl;
    }
    return null;
  }
}

export class LandingIdMacro implements MacroInterface {
  name = 'landing_id';
  description = 'Landing page ID';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (context.landing) {
      return context.landing.id;
    }
    if (context.rawClick?.landingId) {
      return context.rawClick.landingId;
    }
    return null;
  }
}
