/**
 * Language Macro
 */

import type { MacroInterface, MacroContext } from '../types';

export class LanguageMacro implements MacroInterface {
  name = 'language';
  description = 'Browser language';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.language) return null;
    return context.rawClick.language;
  }
}
