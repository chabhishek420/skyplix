/**
 * Cost Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class CostMacro implements MacroInterface {
  name = 'cost';
  description = 'Click cost';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.cost) return '0';
    return String(context.rawClick.cost);
  }
}
