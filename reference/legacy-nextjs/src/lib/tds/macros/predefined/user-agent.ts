/**
 * User Agent Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class UserAgentMacro implements MacroInterface {
  name = 'user_agent';
  description = 'User agent string';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.userAgent) return null;
    return context.rawClick.userAgent;
  }
}
