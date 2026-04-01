/**
 * Macro Registry
 * Central registry for all available macros
 */

import type { MacroInterface } from './types';

// Import predefined macros
import { SubidMacro, SubIdMacro, ClickIdMacro } from './predefined/subid';
import { CampaignIdMacro, CampaignNameMacro } from './predefined/campaign';
import { StreamIdMacro } from './predefined/stream';
import { IpMacro } from './predefined/ip';
import { CountryMacro } from './predefined/country';
import { CityMacro } from './predefined/city';
import { RegionMacro } from './predefined/region';
import { BrowserMacro, BrowserVersionMacro } from './predefined/browser';
import { OsMacro, OsVersionMacro } from './predefined/os';
import { DeviceTypeMacro, DeviceModelMacro, DeviceBrandMacro } from './predefined/device';
import { UserAgentMacro } from './predefined/user-agent';
import { ReferrerMacro } from './predefined/referrer';
import { KeywordMacro } from './predefined/keyword';
import { SourceMacro } from './predefined/source';
import { LanguageMacro } from './predefined/language';
import { CostMacro } from './predefined/cost';
import { DateMacro, TimeMacro, TimestampMacro } from './predefined/datetime';
import { RandomMacro } from './predefined/random';
import { OfferMacro, OfferIdMacro } from './predefined/offer';
import { LandingMacro, LandingIdMacro } from './predefined/landing';

// Import new macros
import {
  VisitorCodeMacro, ProfitMacro, RevenueMacro, SaleRevenueMacro, LeadRevenueMacro,
  CurrencyMacro, StatusMacro, TidMacro, TransactionIdMacro, PayoutMacro,
  Goal1Macro, Goal2Macro, Goal3Macro, Goal4Macro, IsLeadMacro, IsSaleMacro, IsRejectedMacro
} from './predefined/conversion';
import {
  SampleMacro, FromFileMacro, Base64EncodeMacro, Base64DecodeMacro,
  UrlEncodeMacro, UrlDecodeMacro, Md5Macro, Sha256Macro,
  LowercaseMacro, UppercaseMacro, SubstringMacro, ReplaceMacro
} from './predefined/advanced';
import {
  SessionIdMacro, TokenMacro, LpTokenMacro, ParentClickIdMacro, ParentCampaignIdMacro,
  CreativeIdMacro, AdCampaignIdMacro, ExternalIdMacro, GenerateIdMacro, UuidMacro,
  TimestampMsMacro
} from './predefined/tracking';
import {
  ConnectionTypeMacro, OperatorMacro, XRequestedWithMacro,
  CurrentDomainMacro, TrafficSourceNameMacro, DebugMacro
} from './predefined/network';
import {
  OriginalStatusMacro, ConversionCostMacro, ConversionProfitMacro,
  ConversionRevenueMacro, ConversionTimeMacro, AnyClickMacro, AnyConversionMacro
} from './predefined/conversion-ext';

/**
 * Macro Registry
 * Singleton pattern for managing macros
 */
class MacroRegistry {
  private macros: Map<string, MacroInterface> = new Map();
  private aliases: Map<string, string> = new Map();

  constructor() {
    this.registerDefaults();
  }

  /**
   * Register default macros
   */
  private registerDefaults(): void {
    // Click/Sub ID macros
    this.register(new SubidMacro(), ['sub_id', 'clickid', 'click_id']);
    this.register(new SubIdMacro(), []);
    this.register(new ClickIdMacro(), []);

    // Campaign macros
    this.register(new CampaignIdMacro(), ['campaignid']);
    this.register(new CampaignNameMacro(), ['campaignname']);

    // Stream macros
    this.register(new StreamIdMacro(), ['streamid']);

    // Geo macros
    this.register(new CountryMacro(), []);
    this.register(new CityMacro(), []);
    this.register(new RegionMacro(), ['state', 'province']);

    // Device macros
    this.register(new BrowserMacro(), []);
    this.register(new BrowserVersionMacro(), ['browser_version']);
    this.register(new OsMacro(), ['operating_system']);
    this.register(new OsVersionMacro(), ['os_version']);
    this.register(new DeviceTypeMacro(), []);
    this.register(new DeviceModelMacro(), []);
    this.register(new DeviceBrandMacro(), []);

    // Request macros
    this.register(new IpMacro(), ['ip_address']);
    this.register(new UserAgentMacro(), ['ua']);
    this.register(new ReferrerMacro(), ['referer']);
    this.register(new KeywordMacro(), ['kw']);
    this.register(new SourceMacro(), ['traffic_source']);
    this.register(new LanguageMacro(), ['lang']);

    // Revenue macros
    this.register(new CostMacro(), []);

    // DateTime macros
    this.register(new DateMacro(), []);
    this.register(new TimeMacro(), []);
    this.register(new TimestampMacro(), ['ts', 'unix']);

    // Random
    this.register(new RandomMacro(), ['rand']);

    // Offer/Landing
    this.register(new OfferMacro(), []);
    this.register(new OfferIdMacro(), ['offerid']);
    this.register(new LandingMacro(), []);
    this.register(new LandingIdMacro(), ['landingid']);

    // Conversion macros
    this.register(new VisitorCodeMacro(), ['visitor_code']);
    this.register(new ProfitMacro(), []);
    this.register(new RevenueMacro(), []);
    this.register(new SaleRevenueMacro(), ['sale_revenue']);
    this.register(new LeadRevenueMacro(), ['lead_revenue']);
    this.register(new CurrencyMacro(), ['currency']);
    this.register(new StatusMacro(), ['conversion_status']);
    this.register(new TidMacro(), ['tid', 'transaction_id']);
    this.register(new TransactionIdMacro(), []);
    this.register(new PayoutMacro(), ['payout']);
    this.register(new Goal1Macro(), ['goal1']);
    this.register(new Goal2Macro(), ['goal2']);
    this.register(new Goal3Macro(), ['goal3']);
    this.register(new Goal4Macro(), ['goal4']);
    this.register(new IsLeadMacro(), ['is_lead']);
    this.register(new IsSaleMacro(), ['is_sale']);
    this.register(new IsRejectedMacro(), ['is_rejected']);

    // Advanced macros
    this.register(new SampleMacro(), ['sample']);
    this.register(new FromFileMacro(), ['from_file']);
    this.register(new Base64EncodeMacro(), ['base64_encode', 'b64enc']);
    this.register(new Base64DecodeMacro(), ['base64_decode', 'b64dec']);
    this.register(new UrlEncodeMacro(), ['urlencode', 'urlenc']);
    this.register(new UrlDecodeMacro(), ['urldecode', 'urldec']);
    this.register(new Md5Macro(), ['md5']);
    this.register(new Sha256Macro(), ['sha256']);
    this.register(new LowercaseMacro(), ['lower', 'lowercase']);
    this.register(new UppercaseMacro(), ['upper', 'uppercase']);
    this.register(new SubstringMacro(), ['substr', 'substring']);
    this.register(new ReplaceMacro(), ['replace']);

    // Tracking macros
    this.register(new SessionIdMacro(), ['session_id']);
    this.register(new TokenMacro(), ['token']);
    this.register(new LpTokenMacro(), ['lp_token']);
    this.register(new ParentClickIdMacro(), ['parent_click_id']);
    this.register(new ParentCampaignIdMacro(), ['parent_campaign_id']);
    this.register(new CreativeIdMacro(), ['creative_id']);
    this.register(new AdCampaignIdMacro(), ['ad_campaign_id']);
    this.register(new ExternalIdMacro(), ['external_id']);
    this.register(new GenerateIdMacro(), ['gen_id', 'generate_id']);
    this.register(new UuidMacro(), ['uuid']);
    this.register(new TimestampMsMacro(), ['timestamp_ms']);

    // Network / request macros (PHP: ConnectionType, Operator, XRequestedWith, CurrentDomain, TrafficSourceName, Debug)
    this.register(new ConnectionTypeMacro(), ['connection_type', 'conn_type']);
    this.register(new OperatorMacro(), ['operator', 'mobile_operator']);
    this.register(new XRequestedWithMacro(), ['x_requested_with', 'xhr']);
    this.register(new CurrentDomainMacro(), ['current_domain', 'domain']);
    this.register(new TrafficSourceNameMacro(), ['traffic_source_name', 'ts_name']);
    this.register(new DebugMacro(), ['debug']);

    // Conversion-specific macros (PHP: OriginalStatus, ConversionCost/Profit/Revenue/Time, AnyClick, AnyConversion)
    this.register(new OriginalStatusMacro(), ['original_status']);
    this.register(new ConversionCostMacro(), ['conversion_cost']);
    this.register(new ConversionProfitMacro(), ['conversion_profit']);
    this.register(new ConversionRevenueMacro(), ['conversion_revenue']);
    this.register(new ConversionTimeMacro(), ['conversion_time']);
    this.register(new AnyClickMacro(), ['any_click']);
    this.register(new AnyConversionMacro(), ['any_conversion']);
  }

  /**
   * Register a macro
   */
  register(macro: MacroInterface, aliases: string[] = []): void {
    this.macros.set(macro.name.toLowerCase(), macro);
    
    for (const alias of aliases) {
      this.aliases.set(alias.toLowerCase(), macro.name.toLowerCase());
    }
  }

  /**
   * Get macro by name or alias
   */
  getMacro(name: string): MacroInterface | null {
    const lowerName = name.toLowerCase();
    
    // Check direct name
    if (this.macros.has(lowerName)) {
      return this.macros.get(lowerName)!;
    }

    // Check alias
    const aliasTarget = this.aliases.get(lowerName);
    if (aliasTarget && this.macros.has(aliasTarget)) {
      return this.macros.get(aliasTarget)!;
    }

    return null;
  }

  /**
   * Get all registered macro names
   */
  getMacroNames(): string[] {
    return Array.from(this.macros.keys());
  }

  /**
   * Get all aliases
   */
  getAliases(): Map<string, string> {
    return new Map(this.aliases);
  }
}

// Export singleton instance
export const macroRegistry = new MacroRegistry();
