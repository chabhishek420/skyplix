/**
 * Entity Binding Service
 * Binds visitors to streams, landings, and offers for consistent routing
 * Based on Keitaro's EntityBindingService.php
 */

import type { NextRequest, NextResponse } from 'next/server';

/**
 * Binding types
 */
export type BindingType = 'stream' | 'landing' | 'offer';

/**
 * Binding entry
 */
export interface BindingEntry {
  entityId: string;
  entityType: BindingType;
  campaignId: string;
  boundAt: number;
  expiresAt: number;
}

/**
 * Entity Binding Service
 * Manages visitor-to-entity bindings for consistent routing
 */
class EntityBindingService {
  /**
   * Get binding cookie name
   */
  private getCookieName(campaignId: string, type: BindingType): string {
    return `bind_${campaignId.substring(0, 8)}_${type}`;
  }

  /**
   * Get binding from request
   */
  getBinding(request: NextRequest, campaignId: string, type: BindingType): BindingEntry | null {
    const cookieName = this.getCookieName(campaignId, type);
    const cookieValue = request.cookies.get(cookieName)?.value;
    
    if (!cookieValue) return null;
    
    try {
      const decoded = Buffer.from(cookieValue, 'base64').toString('utf-8');
      const parsed = JSON.parse(decoded) as BindingEntry;
      
      // Check if binding is expired
      if (parsed.expiresAt < Date.now()) {
        return null;
      }
      
      return parsed;
    } catch {
      return null;
    }
  }

  /**
   * Set binding on response
   */
  setBinding(
    response: NextResponse,
    campaignId: string,
    entityId: string,
    type: BindingType,
    ttlHours: number = 720 // 30 days default
  ): void {
    const cookieName = this.getCookieName(campaignId, type);
    
    const entry: BindingEntry = {
      entityId,
      entityType: type,
      campaignId,
      boundAt: Date.now(),
      expiresAt: Date.now() + (ttlHours * 3600 * 1000)
    };
    
    const encoded = Buffer.from(JSON.stringify(entry)).toString('base64');
    
    response.cookies.set(cookieName, encoded, {
      maxAge: ttlHours * 3600,
      secure: true,
      httpOnly: true,
      sameSite: 'lax',
      path: '/'
    });
  }

  /**
   * Get stream binding
   */
  getStreamBinding(request: NextRequest, campaignId: string): string | null {
    const binding = this.getBinding(request, campaignId, 'stream');
    return binding?.entityId || null;
  }

  /**
   * Set stream binding
   */
  setStreamBinding(
    response: NextResponse,
    campaignId: string,
    streamId: string,
    ttlHours?: number
  ): void {
    this.setBinding(response, campaignId, streamId, 'stream', ttlHours);
  }

  /**
   * Get landing binding
   */
  getLandingBinding(request: NextRequest, campaignId: string): string | null {
    const binding = this.getBinding(request, campaignId, 'landing');
    return binding?.entityId || null;
  }

  /**
   * Set landing binding
   */
  setLandingBinding(
    response: NextResponse,
    campaignId: string,
    landingId: string,
    ttlHours?: number
  ): void {
    this.setBinding(response, campaignId, landingId, 'landing', ttlHours);
  }

  /**
   * Get offer binding
   */
  getOfferBinding(request: NextRequest, campaignId: string): string | null {
    const binding = this.getBinding(request, campaignId, 'offer');
    return binding?.entityId || null;
  }

  /**
   * Set offer binding
   */
  setOfferBinding(
    response: NextResponse,
    campaignId: string,
    offerId: string,
    ttlHours?: number
  ): void {
    this.setBinding(response, campaignId, offerId, 'offer', ttlHours);
  }

  /**
   * Check if visitor has binding for campaign
   */
  hasBinding(request: NextRequest, campaignId: string, type: BindingType): boolean {
    return this.getBinding(request, campaignId, type) !== null;
  }

  /**
   * Clear binding
   */
  clearBinding(response: NextResponse, campaignId: string, type: BindingType): void {
    const cookieName = this.getCookieName(campaignId, type);
    response.cookies.delete(cookieName);
  }

  /**
   * Clear all bindings for campaign
   */
  clearAllBindings(response: NextResponse, campaignId: string): void {
    this.clearBinding(response, campaignId, 'stream');
    this.clearBinding(response, campaignId, 'landing');
    this.clearBinding(response, campaignId, 'offer');
  }

  /**
   * Get all bindings for campaign
   */
  getAllBindings(request: NextRequest, campaignId: string): {
    streamId: string | null;
    landingId: string | null;
    offerId: string | null;
  } {
    return {
      streamId: this.getStreamBinding(request, campaignId),
      landingId: this.getLandingBinding(request, campaignId),
      offerId: this.getOfferBinding(request, campaignId)
    };
  }

  /**
   * Apply bindings to selection
   * Returns forced selections if bindings exist
   */
  applyBindings(
    request: NextRequest,
    campaignId: string,
    options: {
      bindStream?: boolean;
      bindLanding?: boolean;
      bindOffer?: boolean;
    } = {}
  ): {
    forcedStreamId: string | null;
    forcedLandingId: string | null;
    forcedOfferId: string | null;
  } {
    const result = {
      forcedStreamId: null as string | null,
      forcedLandingId: null as string | null,
      forcedOfferId: null as string | null
    };

    if (options.bindStream !== false) {
      result.forcedStreamId = this.getStreamBinding(request, campaignId);
    }

    if (options.bindLanding !== false) {
      result.forcedLandingId = this.getLandingBinding(request, campaignId);
    }

    if (options.bindOffer !== false) {
      result.forcedOfferId = this.getOfferBinding(request, campaignId);
    }

    return result;
  }

  /**
   * Save bindings after selection
   */
  saveBindings(
    response: NextResponse,
    campaignId: string,
    bindings: {
      streamId?: string;
      landingId?: string;
      offerId?: string;
    },
    options: {
      bindStream?: boolean;
      bindLanding?: boolean;
      bindOffer?: boolean;
      ttlHours?: number;
    } = {}
  ): void {
    if (options.bindStream !== false && bindings.streamId) {
      this.setStreamBinding(response, campaignId, bindings.streamId, options.ttlHours);
    }

    if (options.bindLanding !== false && bindings.landingId) {
      this.setLandingBinding(response, campaignId, bindings.landingId, options.ttlHours);
    }

    if (options.bindOffer !== false && bindings.offerId) {
      this.setOfferBinding(response, campaignId, bindings.offerId, options.ttlHours);
    }
  }
}

// Export singleton instance
export const entityBindingService = new EntityBindingService();
