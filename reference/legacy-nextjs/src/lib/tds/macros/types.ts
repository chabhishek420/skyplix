/**
 * Macros System Types
 * Based on Keitaro TDS Macros Architecture
 */

import type { RawClick, Stream, Campaign, Landing, Offer } from '../pipeline/types';

/**
 * Parser item for macro parsing
 */
export interface ParserItem {
  name: string;
  originalString: string;
  rawMode: boolean;
  arguments: string[];
}

/**
 * Macro context for processing
 */
export interface MacroContext {
  stream: Stream | null;
  rawClick: RawClick | null;
  campaign: Campaign | null;
  landing: Landing | null;
  offer: Offer | null;
  conversion: ConversionData | null;
  params: Record<string, string>;
}

/**
 * Conversion data for macros
 */
export interface ConversionData {
  id: string;
  clickId: string;
  status: string;
  payout: number;
  revenue: number;
  transactionId: string | null;
  offerId: string | null;
  createdAt: Date;
}

/**
 * Abstract macro interface
 */
export interface MacroInterface {
  name: string;
  description: string;
  alwaysRaw: boolean;
  process(context: MacroContext, ...args: string[]): string | null;
}

/**
 * Click macro interface
 */
export interface ClickMacroInterface extends MacroInterface {
  process(context: MacroContext, stream: Stream | null, rawClick: RawClick, ...args: string[]): string | null;
}

/**
 * Conversion macro interface
 */
export interface ConversionMacroInterface extends MacroInterface {
  process(context: MacroContext, stream: Stream | null, conversion: ConversionData, ...args: string[]): string | null;
}

/**
 * Macro registry entry
 */
export interface MacroRegistryEntry {
  name: string;
  macro: MacroInterface;
  aliases: string[];
}
