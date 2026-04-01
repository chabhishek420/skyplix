/**
 * Pipeline Payload
 * Carries state through all pipeline stages
 */

import type { 
  PipelinePayload, 
  RawClick, 
  Campaign, 
  Stream, 
  Landing, 
  Offer, 
  ActionType,
  LogEntry 
} from './types';
import { NextRequest } from 'next/server';

export class Payload implements PipelinePayload {
  // Request/Response
  request: NextRequest | null = null;
  
  // Entities
  rawClick: RawClick | null = null;
  campaign: Campaign | null = null;
  stream: Stream | null = null;
  landing: Landing | null = null;
  offer: Offer | null = null;
  
  // Action
  actionType: ActionType | null = null;
  actionPayload: string | null = null;
  actionOptions: Record<string, unknown> | null = null;
  
  // Forced selections
  forcedStreamId: string | null = null;
  forcedLandingId: string | null = null;
  forcedOfferId: string | null = null;
  forcedCampaignId: string | null = null;
  
  // Flags
  cookieBindStream = false;
  cookieBindLanding = false;
  cookieBindOffer = false;
  aborted = false;
  needToken = false;
  addTokenToUrl = false;
  forceChooseOffer = false;
  forceRedirectOffer = false;
  
  // Response data
  statusCode = 200;
  headers: Record<string, string> = {};
  cookies: string[] = [];
  body: string | null = null;
  redirectUrl: string | null = null;

  contentType = 'text/html';
  
  // Logs
  logs: string[] = [];
  
  // Clicks to store (for batch operations)
  private _rawClicksToStore: RawClick[] = [];
  
  // Pipeline recursion tracking (CRITICAL: prevent infinite loops)
  // Matches PHP: Pipeline::LIMIT = 10
  private _repeatCount = 0;
  static readonly MAX_REPEATS = 10;
  
  // Pipeline level (1 = first level, 2 = second level for LP→Offer)
  private _pipelineLevel: 1 | 2 = 1;
  
  // Save token and uniqueness flags
  saveToken = false;
  saveUniquenessId = false;

  constructor(request: NextRequest) {
    this.request = request;
  }

  /**
   * Create payload from request
   */
  static fromRequest(request: NextRequest): Payload {
    return new Payload(request);
  }

  /**
   * Set raw click data
   */
  setRawClick(rawClick: RawClick): this {
    this.rawClick = rawClick;
    return this;
  }

  /**
   * Set campaign
   */
  setCampaign(campaign: Campaign): this {
    this.campaign = campaign;
    return this;
  }

  /**
   * Set stream
   */
  setStream(stream: Stream): this {
    this.stream = stream;
    return this;
  }

  /**
   * Set landing
   */
  setLanding(landing: Landing): this {
    this.landing = landing;
    return this;
  }

  /**
   * Set offer
   */
  setOffer(offer: Offer): this {
    this.offer = offer;
    return this;
  }

  /**
   * Set action
   */
  setAction(type: ActionType, payload: string, options?: Record<string, unknown>): this {
    this.actionType = type;
    this.actionPayload = payload;
    this.actionOptions = options || null;
    return this;
  }

  /**
   * Set redirect
   */
  setRedirect(url: string, status: number = 302): this {
    this.redirectUrl = url;
    this.statusCode = status;
    return this;
  }

  /**
   * Set response body
   */
  setBody(body: string, contentType: string = 'text/html'): this {
    this.body = body;
    this.headers['Content-Type'] = contentType;
    return this;
  }

  /**
   * Add header
   */
  addHeader(name: string, value: string): this {
    this.headers[name] = value;
    return this;
  }

  /**
   * Log message
   */
  log(message: string): this {
    this.logs.push(`[${new Date().toISOString()}] ${message}`);
    return this;
  }

  /**
   * Abort pipeline
   */
  abort(): this {
    this.aborted = true;
    return this;
  }

  /**
   * Enable cookie binding for stream
   */
  enableCookieBindStream(): this {
    this.cookieBindStream = true;
    return this;
  }

  /**
   * Enable cookie binding for landing
   */
  enableCookieBindLanding(): this {
    this.cookieBindLanding = true;
    return this;
  }

  /**
   * Enable cookie binding for offer
   */
  enableCookieBindOffer(): this {
    this.cookieBindOffer = true;
    return this;
  }

  /**
   * Set need token flag
   */
  setNeedToken(need: boolean): this {
    this.needToken = need;
    return this;
  }

  /**
   * Set add token to URL flag
   */
  setAddTokenToUrl(add: boolean): this {
    this.addTokenToUrl = add;
    return this;
  }

  /**
   * Set force choose offer flag
   */
  isForceChooseOffer(): boolean {
    return this.forceChooseOffer;
  }

  /**
   * Set force redirect to offer flag
   */
  isForceRedirectOffer(): boolean {
    return this.forceRedirectOffer;
  }

  /**
   * Set force choose offer
   */
  setForceChooseOffer(force: boolean): this {
    this.forceChooseOffer = force;
    return this;
  }

  /**
   * Set force redirect to offer
   */
  setForceRedirectOffer(force: boolean): this {
    this.forceRedirectOffer = force;
    return this;
  }

  /**
   * Get request parameter
   */
  getParam(name: string): string | null {
    if (!this.request) return null;
    const url = new URL(this.request.url);
    return url.searchParams.get(name);
  }

  /**
   * Get all request parameters
   */
  getAllParams(): Record<string, string> {
    const params: Record<string, string> = {};
    if (!this.request) return params;
    const url = new URL(this.request.url);
    url.searchParams.forEach((value, key) => {
      params[key] = value;
    });
    return params;
  }

  /**
   * Get request header
   */
  getHeader(name: string): string | null {
    if (!this.request) return null;
    return this.request.headers.get(name);
  }

  /**
   * Get visitor IP
   */
  getIp(): string {
    if (!this.request) return 'unknown';
    
    // Check Cloudflare header
    const cfIp = this.request.headers.get('cf-connecting-ip');
    if (cfIp) return cfIp;
    
    // Check X-Forwarded-For
    const forwarded = this.request.headers.get('x-forwarded-for');
    if (forwarded) return forwarded.split(',')[0].trim();
    
    // Check X-Real-IP
    const realIp = this.request.headers.get('x-real-ip');
    if (realIp) return realIp;
    
    return 'unknown';
  }

  /**
   * Get user agent
   */
  getUserAgent(): string {
    if (!this.request) return '';
    return this.request.headers.get('user-agent') || '';
  }

  /**
   * Get referrer
   */
  getReferrer(): string | null {
    if (!this.request) return null;
    return this.request.headers.get('referer');
  }

  /**
   * Get language
   */
  getLanguage(): string | null {
    if (!this.request) return null;
    const lang = this.request.headers.get('accept-language');
    if (!lang) return null;
    return lang.split(',')[0].substring(0, 2).toUpperCase();
  }
  
  // ============================================
  // Getter/Setter methods for entities
  // ============================================
  
  /**
   * Get raw click
   */
  getRawClick(): RawClick | null {
    return this.rawClick;
  }
  
  /**
   * Get campaign
   */
  getCampaign(): Campaign | null {
    return this.campaign;
  }
  
  /**
   * Get stream
   */
  getStream(): Stream | null {
    return this.stream;
  }
  
  /**
   * Get landing
   */
  getLanding(): Landing | null {
    return this.landing;
  }
  
  /**
   * Get offer
   */
  getOffer(): Offer | null {
    return this.offer;
  }
  
  /**
   * Get forced stream ID
   */
  getForcedStreamId(): string | null {
    return this.forcedStreamId;
  }
  
  /**
   * Set forced stream ID
   */
  setForcedStreamId(id: string): this {
    this.forcedStreamId = id;
    return this;
  }
  
  /**
   * Get forced campaign ID
   */
  getForcedCampaignId(): string | null {
    return this.forcedCampaignId;
  }
  
  /**
   * Set forced campaign ID
   */
  setForcedCampaignId(id: string): this {
    this.forcedCampaignId = id;
    return this;
  }
  
  /**
   * Get forced landing ID
   */
  getForcedLandingId(): string | null {
    return this.forcedLandingId;
  }
  
  /**
   * Set forced landing ID
   */
  setForcedLandingId(id: string): this {
    this.forcedLandingId = id;
    return this;
  }
  
  /**
   * Get forced offer ID
   */
  getForcedOfferId(): string | null {
    return this.forcedOfferId;
  }
  
  /**
   * Set forced offer ID
   */
  setForcedOfferId(id: string): this {
    this.forcedOfferId = id;
    return this;
  }
  
  /**
   * Add raw click to store queue
   */
  addRawClickToStore(rawClick: RawClick): this {
    this._rawClicksToStore.push(rawClick);
    return this;
  }
  
  /**
   * Get raw clicks to store
   */
  getRawClicksToStore(): RawClick[] {
    return this._rawClicksToStore;
  }
  
  /**
   * Set status code
   */
  setStatus(code: number): this {
    this.statusCode = code;
    return this;
  }

  
  // ============================================
  // Recursion Tracking (CRITICAL for campaign redirects)
  // ============================================
  
  /**
   * Get current repeat count
   */
  getRepeatCount(): number {
    return this._repeatCount;
  }
  
  /**
   * Increment repeat count
   */
  incrementRepeatCount(): this {
    this._repeatCount++;
    return this;
  }
  
  /**
   * Check if max repeats exceeded
   */
  isMaxRepeatsExceeded(): boolean {
    return this._repeatCount >= Payload.MAX_REPEATS;
  }
  
  /**
   * Reset for next campaign iteration
   * Called when redirecting to another campaign
   * IMPORTANT: Call setForcedCampaignId BEFORE this if needed
   */
  resetForCampaignRedirect(): this {
    // Preserve forced campaign ID for next iteration
    const nextCampaignId = this.forcedCampaignId;
    
    // Keep raw click data but reset entity selections
    this.campaign = null;
    this.stream = null;
    this.landing = null;
    this.offer = null;
    this.actionType = null;
    this.actionPayload = null;
    this.actionOptions = null;
    this.forcedStreamId = null;
    this.forcedLandingId = null;
    this.forcedOfferId = null;
    this.forcedCampaignId = null;
    this.aborted = false;
    
    // Increment repeat counter
    this.incrementRepeatCount();
    
    // Restore forced campaign ID for next iteration
    if (nextCampaignId) {
      this.forcedCampaignId = nextCampaignId;
    }
    
    this.log(`Reset payload for campaign redirect (repeat ${this._repeatCount}/${Payload.MAX_REPEATS})`);
    
    return this;
  }
  
  // ============================================
  // Pipeline Level Tracking
  // ============================================
  
  /**
   * Get pipeline level
   */
  getPipelineLevel(): 1 | 2 {
    return this._pipelineLevel;
  }
  
  /**
   * Set pipeline level
   */
  setPipelineLevel(level: 1 | 2): this {
    this._pipelineLevel = level;
    return this;
  }
  
  /**
   * Check if second level pipeline
   */
  isSecondLevel(): boolean {
    return this._pipelineLevel === 2;
  }
  
  // ============================================
  // Save Flags
  // ============================================
  
  /**
   * Enable save token
   */
  enableSaveToken(): this {
    this.saveToken = true;
    return this;
  }
  
  /**
   * Check if save token required
   */
  isSaveTokenRequired(): boolean {
    return this.saveToken;
  }
  
  /**
   * Enable save uniqueness ID
   */
  enableSaveUniquenessId(): this {
    this.saveUniquenessId = true;
    return this;
  }
  
  /**
   * Check if save uniqueness required
   */
  isSaveUniquenessRequired(): boolean {
    return this.saveUniquenessId;
  }
}
