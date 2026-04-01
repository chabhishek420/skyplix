/**
 * Browser Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class BrowserMacro implements MacroInterface {
  name = 'browser';
  description = 'Browser name';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.browser) return null;
    return context.rawClick.browser;
  }
}

export class BrowserVersionMacro implements MacroInterface {
  name = 'browser_version';
  description = 'Browser version';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.browserVersion) return null;
    return context.rawClick.browserVersion;
  }
}
