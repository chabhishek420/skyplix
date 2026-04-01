/**
 * Keyword Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class KeywordMacro implements MacroInterface {
  name = 'keyword';
  description = 'Search keyword';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.keyword) return null;
    return context.rawClick.keyword;
  }
}
