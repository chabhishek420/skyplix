/**
 * Pipeline Types
 * Based on Keitaro TDS Pipeline Architecture
 */

import { NextRequest } from 'next/server';

// Raw Click Data - mirrors Keitaro's RawClick model
export interface RawClick {
  // Core identifiers
  clickId: string;
  visitorCode: string;
  campaignId: string | null;
  streamId: string | null;
  landingId: string | null;
  offerId: string | null;
  affiliateNetworkId: string | null;
  
  // Request data
  ip: string;
  ipString: string;
  userAgent: string;
  referrer: string | null;
  seReferrer: string | null;
  language: string | null;
  
  // Geo data
  country: string | null;
  region: string | null;
  city: string | null;
  isp: string | null;
  operator: string | null;
  connectionType: string | null;
  
  // Device data
  browser: string | null;
  browserVersion: string | null;
  os: string | null;
  osVersion: string | null;
  deviceType: string | null;
  deviceModel: string | null;
  deviceBrand: string | null;
  isMobile: boolean;
  
  // Traffic data
  source: string | null;
  keyword: string | null;
  searchEngine: string | null;
  xRequestedWith: string | null;
  subId: string | null;
  subId1: string | null;
  subId2: string | null;
  subId3: string | null;
  subId4: string | null;
  subId5: string | null;
  subId6: string | null;
  subId7: string | null;
  subId8: string | null;
  subId9: string | null;
  subId10: string | null;
  subId11: string | null;
  subId12: string | null;
  subId13: string | null;
  subId14: string | null;
  subId15: string | null;
  
  // Extra params (Keitaro has 10)
  extraParam1: string | null;
  extraParam2: string | null;
  extraParam3: string | null;
  
  // Destination
  destination: string | null;
  landingUrl: string | null;
  
  // Detection flags
  isBot: boolean;
  botReason: string | null;
  botType: string | null;
  botConfidence: number;
  isUsingProxy: boolean;
  
  // Uniqueness flags
  isUniqueCampaign: boolean;
  isUniqueStream: boolean;
  isUniqueGlobal: boolean;
  
  // Resolution flags
  isGeoResolved: boolean;
  isDeviceResolved: boolean;
  isIspResolved: boolean;
  
  // Revenue tracking
  isLead: boolean;
  isSale: boolean;
  isRejected: boolean;
  leadRevenue: number | null;
  saleRevenue: number | null;
  rejectedRevenue: number | null;
  cost: number | null;
  
  // Tracking
  parentCampaignId: string | null;
  parentSubId: string | null;
  token: string | null;
  creativeId: string | null;
  adCampaignId: string | null;
  externalId: string | null;
  
  // Session
  sessionId: string | null;
  
  // Landing clicked (for LP -> Offer flow)
  landingClicked?: boolean;
  landingClickedAt?: Date | null;
  
  // Timestamp
  datetime: Date;
}

// Campaign entity
export interface Campaign {
  id: string;
  campaignId: number;
  name: string;
  alias?: string;
  status: 'active' | 'paused' | 'deleted';
  type: 'position' | 'weight';
  redirectType: 'http302' | 'http301' | 'meta' | 'js' | 'frame';
  cookiesTtl: number;
  bindVisitors: boolean;
  bindVisitorsLanding: boolean;
  bindVisitorsOffer: boolean;
  cloakingEnabled: boolean;
  safePageUrl: string | null;
  destinationUrl: string | null;
  offerId: number | null;
  affiliateId: string | null;
  token?: string | null;
  
  // Traffic source tracking params
  trafficSource?: {
    keywordParam: string;
    costParam: string;
    sourceParam: string;
  };
}

// Stream filter (for matching traffic)
export interface StreamFilter {
  id: string;
  streamId: string;
  name: string;
  mode: 'accept' | 'reject';
  payload: Record<string, unknown>;
}

// Stream entity
export interface Stream {
  id: string;
  campaignId: string;
  name: string;
  alias?: string;
  type: 'forced' | 'regular' | 'default';
  schema: 'url' | 'landings' | 'offers' | 'action';
  actionType: string | null;
  actionPayload: string | null;
  actionOptions: Record<string, unknown> | null;
  weight: number;
  position: number;
  status: 'active' | 'paused' | 'deleted';
  collectClicks: boolean;
  filterOr: boolean;
  
  // Stream filters for traffic matching
  filters?: StreamFilter[];
  
  // Stream-specific landings/offers
  landings?: Landing[];
  offers?: Offer[];
}

// Landing page
export interface Landing {
  id: string;
  streamId: string;
  name: string;
  url: string;
  weight: number;
  status: 'active' | 'paused' | 'deleted';
  actionType?: string | null;
  actionPayload?: string | null;
  actionOptions?: Record<string, unknown> | null;
  landingType?: string;
}

// Offer
export interface Offer {
  id: string;
  streamId: string;
  name: string;
  url: string;
  affiliateNetworkId: string | null;
  payout: number | null;
  payoutCurrency?: string | null;
  payoutType?: string | null;
  weight: number;
  status: 'active' | 'paused' | 'deleted';
  actionType?: string | null;
  actionPayload?: string | null;
  actionOptions?: Record<string, unknown> | null;
  offerType?: string;
  country?: string | null;
  dailyCap?: number | null;
  alternativeOfferId?: string | null;
}

// Action types (from Keitaro) — 19 types total matching PHP ActionRepository
export type ActionType = 
  | 'remote'          // Fetch URL from remote and redirect
  | 'http_redirect'   // HTTP 302 redirect
  | 'http301'         // HTTP 301 redirect
  | 'meta'            // Meta refresh redirect
  | 'double_meta'     // Double meta to hide referrer
  | 'iframe'          // Load in iframe
  | 'frame'           // Frame redirect
  | 'js'              // JavaScript redirect
  | 'js_for_iframe'   // JS redirect for iframe contexts
  | 'js_for_script'   // JS redirect as application/javascript
  | 'blank_referrer'  // Load URL while blanking the referrer
  | 'local_file'      // Serve local file from upload folder
  | 'show_html'       // Display HTML
  | 'show_text'       // Display text
  | 'status404'       // Return 404
  | 'do_nothing'      // Empty response
  | 'to_campaign'     // Redirect to another campaign
  | 'sub_id'          // Generate sub_id
  | 'curl'            // Execute cURL request
  | 'form_submit';    // Auto-submit form with POST data



// Pipeline payload interface
export interface PipelinePayload {
  // Request/Response
  request: NextRequest | null;
  
  // Entities
  rawClick: RawClick | null;
  campaign: Campaign | null;
  stream: Stream | null;
  landing: Landing | null;
  offer: Offer | null;
  
  // Action
  actionType: ActionType | null;
  actionPayload: string | null;
  actionOptions: Record<string, unknown> | null;
  
  // Forced selections
  forcedStreamId: string | null;
  forcedLandingId: string | null;
  forcedOfferId: string | null;
  forcedCampaignId: string | null;
  
  // Flags
  cookieBindStream: boolean;
  cookieBindLanding: boolean;
  cookieBindOffer: boolean;
  aborted: boolean;
  needToken: boolean;
  addTokenToUrl: boolean;
  forceChooseOffer: boolean;
  forceRedirectOffer: boolean;
  saveToken: boolean;
  saveUniquenessId: boolean;
  
  // Response data
  statusCode: number;
  headers: Record<string, string>;
  cookies: string[];
  body: string | null;
  redirectUrl: string | null;
  contentType: string;
  
  // Logs
  logs: string[];
  
  // Core Methods
  log(message: string): this;
  abort(): this;
  setBody(body: string, contentType?: string): this;
  setRedirect(url: string, status?: number): this;
  addHeader(name: string, value: string): this;
  
  // Entity Getters/Setters
  getRawClick(): RawClick | null;
  setRawClick(rawClick: RawClick): this;
  getCampaign(): Campaign | null;
  setCampaign(campaign: Campaign): this;
  getStream(): Stream | null;
  setStream(stream: Stream): this;
  getLanding(): Landing | null;
  setLanding(landing: Landing): this;
  getOffer(): Offer | null;
  setOffer(offer: Offer): this;
  
  // Request Methods
  getParam(name: string): string | null;
  getAllParams(): Record<string, string>;
  getHeader(name: string): string | null;
  getIp(): string;
  getUserAgent(): string;
  getReferrer(): string | null;
  getLanguage(): string | null;
  
  // Selection Methods
  getForcedStreamId(): string | null;
  getForcedLandingId(): string | null;
  getForcedOfferId(): string | null;
  getForcedCampaignId(): string | null;
  setForcedStreamId(id: string): this;
  setForcedLandingId(id: string): this;
  setForcedOfferId(id: string): this;
  setForcedCampaignId(id: string): this;
  
  // Action Methods
  setAction(type: ActionType, payload: string, options?: Record<string, unknown>): this;
  
  // Token Methods
  setNeedToken(value: boolean): this;
  setAddTokenToUrl(value: boolean): this;
  isForceChooseOffer(): boolean;
  isForceRedirectOffer(): boolean;

  // Recursion Methods
  getRepeatCount(): number;
  incrementRepeatCount(): this;
  isMaxRepeatsExceeded(): boolean;
  resetForCampaignRedirect(): this;
  
  // Level Methods
  getPipelineLevel(): 1 | 2;
  setPipelineLevel(level: 1 | 2): this;
  isSecondLevel(): boolean;
  
  // Capture Methods
  addRawClickToStore(rawClick: RawClick): this;
  getRawClicksToStore(): RawClick[];
}



// Stage result
export interface StageResult {
  success: boolean;
  payload: PipelinePayload;
  error?: string;
  abort?: boolean;
}

// Stage interface
export interface StageInterface {
  name: string;
  process(payload: PipelinePayload): Promise<StageResult>;
}

// Bot detection result
export interface BotDetectionResult {
  isBot: boolean;
  reason: string | null;
  confidence: number;
  botType: 'crawler' | 'scanner' | 'tool' | 'suspicious' | 'debug' | null;
}

// Device info
export interface DeviceInfo {
  browser: string;
  browserVersion: string;
  os: string;
  osVersion: string;
  deviceType: string;
  deviceModel: string;
  deviceBrand: string;
  isMobile: boolean;
}

// Geo info
export interface GeoInfo {
  country: string;
  region: string;
  city: string;
  isp: string;
  operator: string;
  connectionType: string;
}

// Log entry
export interface LogEntry {
  timestamp: Date;
  stage: string;
  message: string;
  data?: Record<string, unknown>;
}
