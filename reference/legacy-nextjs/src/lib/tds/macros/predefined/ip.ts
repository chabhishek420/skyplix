/**
 * IP Macro
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
