/**
 * Tracking-related Macros
 * Click ID, Session ID, Token macros
 */

import type { MacroInterface, MacroContext } from '../types';
import crypto from 'crypto';

/**
 * Session ID Macro
 */
export class SessionIdMacro implements MacroInterface {
  name = 'session_id';
  description = 'Session ID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.sessionId || null;
  }
}

/**
 * Token Macro
 */
export class TokenMacro implements MacroInterface {
  name = 'token';
  description = 'LP token';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.token || null;
  }
}

/**
 * LP Token Macro (alias)
 */
export class LpTokenMacro implements MacroInterface {
  name = 'lp_token';
  description = 'Landing page token';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.token || null;
  }
}

/**
 * Parent Click ID Macro
 */
export class ParentClickIdMacro implements MacroInterface {
  name = 'parent_click_id';
  description = 'Parent click ID (for campaign redirects)';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.parentSubId || null;
  }
}

/**
 * Parent Campaign ID Macro
 */
export class ParentCampaignIdMacro implements MacroInterface {
  name = 'parent_campaign_id';
  description = 'Parent campaign ID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.parentCampaignId || null;
  }
}

/**
 * Creative ID Macro
 */
export class CreativeIdMacro implements MacroInterface {
  name = 'creative_id';
  description = 'Creative ID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.creativeId || null;
  }
}

/**
 * Ad Campaign ID Macro
 */
export class AdCampaignIdMacro implements MacroInterface {
  name = 'ad_campaign_id';
  description = 'Ad campaign ID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.adCampaignId || null;
  }
}

/**
 * External ID Macro
 */
export class ExternalIdMacro implements MacroInterface {
  name = 'external_id';
  description = 'External ID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.externalId || null;
  }
}

/**
 * Generate ID Macro
 * Generates a new unique ID
 */
export class GenerateIdMacro implements MacroInterface {
  name = 'gen_id';
  description = 'Generate unique ID';
  alwaysRaw = false;
  
  process(context: MacroContext, length?: string, format?: string): string | null {
    const len = parseInt(length || '16', 10);
    const fmt = format || 'hex';
    
    const bytes = crypto.randomBytes(Math.ceil(len / 2));
    
    switch (fmt) {
      case 'hex':
        return bytes.toString('hex').substring(0, len);
      case 'base64':
        return bytes.toString('base64').substring(0, len);
      case 'base64url':
        return bytes.toString('base64url').substring(0, len);
      default:
        return bytes.toString('hex').substring(0, len);
    }
  }
}

/**
 * UUID Macro
 * Generates a UUID
 */
export class UuidMacro implements MacroInterface {
  name = 'uuid';
  description = 'Generate UUID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return crypto.randomUUID();
  }
}

/**
 * Timestamp MS Macro
 * Returns Unix timestamp in milliseconds
 */
export class TimestampMsMacro implements MacroInterface {
  name = 'timestamp_ms';
  description = 'Unix timestamp in milliseconds';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return Date.now().toString();
  }
}
