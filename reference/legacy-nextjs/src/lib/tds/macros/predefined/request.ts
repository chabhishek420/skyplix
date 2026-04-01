/**
 * Request Macros (IP, User-Agent, Referrer, etc.)
 */

import type { MacroInterface, MacroContext } from '../types';

export class IpMacro implements MacroInterface {
  name = 'ip';
  description = 'Visitor IP address';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.ipString) return null;
    return context.rawClick.ipString;
  }
}

export class UserAgentMacro implements MacroInterface {
  name = 'user_agent';
  description = 'User agent string';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.userAgent) return null;
    return context.rawClick.userAgent;
  }
}

export class ReferrerMacro implements MacroInterface {
  name = 'referrer';
  description = 'Referrer URL';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.referrer) return null;
    return context.rawClick.referrer;
  }
}

export class KeywordMacro implements MacroInterface {
  name = 'keyword';
  description = 'Search keyword';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.keyword) return null;
    return context.rawClick.keyword;
  }
}

export class SourceMacro implements MacroInterface {
  name = 'source';
  description = 'Traffic source';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.source) return null;
    return context.rawClick.source;
  }
}

export class LanguageMacro implements MacroInterface {
  name = 'language';
  description = 'Browser language';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.language) return null;
    return context.rawClick.language;
  }
}
