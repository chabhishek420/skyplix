/**
 * Campaign Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class CampaignIdMacro implements MacroInterface {
  name = 'campaign_id';
  description = 'Campaign ID';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (context.campaign) {
      return String(context.campaign.campaignId || context.campaign.id);
    }
    if (context.rawClick?.campaignId) {
      return context.rawClick.campaignId;
    }
    return null;
  }
}

export class CampaignNameMacro implements MacroInterface {
  name = 'campaign_name';
  description = 'Campaign name';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (context.campaign) {
      return context.campaign.name;
    }
    return null;
  }
}
