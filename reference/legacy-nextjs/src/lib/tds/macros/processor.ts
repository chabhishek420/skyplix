/**
 * Macros Processor
 * Parses and replaces macros in URLs and content
 * Based on Keitaro TDS MacrosProcessor
 */

import type { MacroContext, ParserItem } from './types';
import { macroRegistry } from './registry';

/**
 * Macros Processor
 * Handles parsing and replacement of {macro_name} and $macro_name patterns
 */
export class MacrosProcessor {
  /**
   * Process content and replace all macros
   */
  static process(content: string, context: MacroContext): string {
    if (!content || (!content.includes('$') && !content.includes('{'))) {
      return content;
    }

    const processor = new MacrosProcessor();
    return processor.processContent(content, context);
  }

  /**
   * Process content
   */
  processContent(content: string, context: MacroContext): string {
    const parserItems = this.parseForMacros(content);

    for (const item of parserItems) {
      const value = this.searchInMacroScripts(item, context);
      if (value !== null) {
        content = this.replace(content, item, value);
        continue;
      }

      const paramValue = this.searchInParams(item, context.params);
      if (paramValue !== null) {
        content = this.replace(content, item, paramValue);
      }
    }

    return content;
  }

  /**
   * Parse content for macro patterns
   * Supports: {macro_name}, {macro_name:arg1,arg2}, $macro_name, $_macro_name
   */
  parseForMacros(content: string): ParserItem[] {
    const patterns = [
      /{(_?)([a-z0-9_\-]+):?([^{^}]*?)}/gi,
      /\$(_?)([a-z0-9_-]+)/gi
    ];

    const items: ParserItem[] = [];
    const seen = new Set<string>();

    for (const pattern of patterns) {
      const matches = content.matchAll(pattern);
      for (const match of matches) {
        const originalString = match[0];
        
        // Skip duplicates
        if (seen.has(originalString)) continue;
        seen.add(originalString);

        const rawMode = match[1] === '_';
        const name = match[2].toLowerCase();
        const args = match[3] ? match[3].split(',').map(a => a.trim()) : [];

        items.push({
          name,
          originalString,
          rawMode,
          arguments: args
        });
      }
    }

    return items;
  }

  /**
   * Search in macro scripts (registered macros)
   */
  searchInMacroScripts(item: ParserItem, context: MacroContext): string | null {
    const macro = macroRegistry.getMacro(item.name);
    if (!macro) {
      return null;
    }

    if (macro.alwaysRaw) {
      item.rawMode = true;
    }

    try {
      const value = macro.process(context, ...item.arguments);
      if (value === null) {
        item.rawMode = true;
        return item.originalString;
      }
      return value;
    } catch (error) {
      console.error(`Macro ${item.name} error:`, error);
      return null;
    }
  }

  /**
   * Search in request parameters
   */
  searchInParams(item: ParserItem, params: Record<string, string>): string | null {
    if (!params || !(item.name in params)) {
      return null;
    }

    let value = params[item.name];
    if (Array.isArray(value)) {
      value = JSON.stringify(value);
    }

    return String(value);
  }

  /**
   * Replace macro in content
   */
  replace(content: string, item: ParserItem, value: string): string {
    if (!item.rawMode) {
      value = encodeURIComponent(value);
    }
    return content.split(item.originalString).join(value);
  }
}

/**
 * Process URL with macros
 */
export function processUrl(url: string, context: MacroContext): string {
  return MacrosProcessor.process(url, context);
}

/**
 * Process HTML content with macros
 */
export function processHtml(html: string, context: MacroContext): string {
  return MacrosProcessor.process(html, context);
}

/**
 * Process macros synchronously (wrapper for MacrosProcessor.process)
 */
export function processMacros(content: string, context: MacroContext): string {
  return MacrosProcessor.process(content, context);
}

/**
 * Default macro processor instance
 */
export const macroProcessor = {
  process: MacrosProcessor.process,
  processUrl,
  processHtml
};
