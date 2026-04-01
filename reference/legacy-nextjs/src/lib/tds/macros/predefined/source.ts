/**
 * Source Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class SourceMacro implements MacroInterface {
  name = 'source';
  description = 'Traffic source';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.source) return null;
    return context.rawClick.source;
  }
}
