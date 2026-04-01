/**
 * LP Token Service
 * Manages landing page tokens for LP → Offer flow tracking
 * Based on Keitaro's LpTokenService.php
 */

import crypto from 'crypto';

/**
 * LP Token payload
 */
export interface LpTokenPayload {
  clickId: string;
  campaignId: string;
  streamId: string;
  landingId?: string;
  offerId?: string;
  timestamp: number;
  signature: string;
}

/**
 * LP Token Service
 * Generates and validates tokens for LP → Offer tracking
 */
class LpTokenService {
  private secret: string;
  private tokenTtl = 3600000; // 1 hour default TTL

  constructor() {
    this.secret = process.env.LP_TOKEN_SECRET || 'default-lp-token-secret-change-in-production';
  }

  /**
   * Generate LP token
   */
  generateToken(payload: Omit<LpTokenPayload, 'timestamp' | 'signature'>): string {
    const tokenData: LpTokenPayload = {
      ...payload,
      timestamp: Date.now(),
      signature: ''
    };

    // Generate signature
    tokenData.signature = this.generateSignature(tokenData);

    // Encode token
    const json = JSON.stringify(tokenData);
    return Buffer.from(json).toString('base64url');
  }

  /**
   * Parse and validate LP token
   */
  parseToken(token: string): LpTokenPayload | null {
    try {
      const json = Buffer.from(token, 'base64url').toString('utf-8');
      const payload = JSON.parse(json) as LpTokenPayload;

      // Validate signature
      const expectedSignature = this.generateSignature(payload);
      if (payload.signature !== expectedSignature) {
        return null;
      }

      // Check expiration
      if (Date.now() - payload.timestamp > this.tokenTtl) {
        return null;
      }

      return payload;
    } catch {
      return null;
    }
  }

  /**
   * Validate token for specific click
   */
  validateToken(token: string, clickId: string): boolean {
    const payload = this.parseToken(token);
    return payload?.clickId === clickId;
  }

  /**
   * Get click ID from token
   */
  getClickIdFromToken(token: string): string | null {
    const payload = this.parseToken(token);
    return payload?.clickId || null;
  }

  /**
   * Get campaign ID from token
   */
  getCampaignIdFromToken(token: string): string | null {
    const payload = this.parseToken(token);
    return payload?.campaignId || null;
  }

  /**
   * Generate signature for token payload
   */
  private generateSignature(payload: Omit<LpTokenPayload, 'signature'>): string {
    const data = `${payload.clickId}|${payload.campaignId}|${payload.streamId}|${payload.timestamp}`;
    return crypto
      .createHmac('sha256', this.secret)
      .update(data)
      .digest('hex')
      .substring(0, 32);
  }

  /**
   * Create token URL with token parameter
   */
  addTokenToUrl(url: string, token: string, paramName: string = 'lp_token'): string {
    try {
      const urlObj = new URL(url);
      urlObj.searchParams.set(paramName, token);
      return urlObj.toString();
    } catch {
      return url;
    }
  }

  /**
   * Extract token from URL
   */
  extractTokenFromUrl(url: string, paramName: string = 'lp_token'): string | null {
    try {
      const urlObj = new URL(url);
      return urlObj.searchParams.get(paramName);
    } catch {
      return null;
    }
  }

  /**
   * Generate offer URL with LP token
   */
  generateOfferUrl(
    offerUrl: string,
    clickId: string,
    campaignId: string,
    streamId: string,
    landingId?: string
  ): string {
    const token = this.generateToken({
      clickId,
      campaignId,
      streamId,
      landingId
    });

    return this.addTokenToUrl(offerUrl, token);
  }

  /**
   * Set token TTL (milliseconds)
   */
  setTokenTtl(ttl: number): void {
    this.tokenTtl = ttl;
  }
}

// Export singleton instance
export const lpTokenService = new LpTokenService();
