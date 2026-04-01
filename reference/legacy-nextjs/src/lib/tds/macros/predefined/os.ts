/**
 * OS Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class OsMacro implements MacroInterface {
  name = 'os';
  description = 'Operating system';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.os) return null;
    return context.rawClick.os;
  }
}

export class OsVersionMacro implements MacroInterface {
  name = 'os_version';
  description = 'OS version';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.osVersion) return null;
    return context.rawClick.osVersion;
  }
}
