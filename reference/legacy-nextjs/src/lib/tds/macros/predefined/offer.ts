/**
 * Offer Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class OfferMacro implements MacroInterface {
  name = 'offer';
  description = 'Offer URL';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (context.offer?.url) {
      return context.offer.url;
    }
    return null;
  }
}

export class OfferIdMacro implements MacroInterface {
  name = 'offer_id';
  description = 'Offer ID';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (context.offer) {
      return context.offer.id;
    }
    if (context.rawClick?.offerId) {
      return context.rawClick.offerId;
    }
    return null;
  }
}
