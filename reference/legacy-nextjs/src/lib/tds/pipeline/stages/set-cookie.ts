/**
 * SetCookieStage
 * 
 * Sets tracking cookies on the response.
 * Based on Keitaro's SetCookieStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { cookiesService } from '../../services/cookies-service';


export class SetCookieStage implements StageInterface {
  name = 'SetCookieStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;

    const campaign = payload.campaign;
    const cookiesTtl = campaign?.cookiesTtl || 24; // Default 24 hours

    // 1. Visitor code cookie
    if (rawClick.visitorCode) {
      const vcName = `vc_${rawClick.campaignId || 'default'}`;
      payload.cookies.push(cookiesService.formatCookie('visitor', rawClick.visitorCode, { 
        name: vcName,
        ttl: cookiesTtl 
      }));
    }

    // 2. Click ID cookie
    if (rawClick.clickId) {
      const clickName = `clickid_${rawClick.campaignId || 'default'}`;
      payload.cookies.push(cookiesService.formatCookie('session', rawClick.clickId, { 
        name: clickName,
        ttl: cookiesTtl 
      }));
    }

    // 3. Stream binding cookie
    if (payload.cookieBindStream && rawClick.streamId) {
      const streamName = `stream_${rawClick.campaignId}`;
      payload.cookies.push(cookiesService.formatCookie('binding', rawClick.streamId, { 
        name: streamName,
        ttl: cookiesTtl 
      }));
    }

    // 4. Landing binding cookie
    if (payload.cookieBindLanding && rawClick.landingId) {
      const landingName = `landing_${rawClick.campaignId}`;
      payload.cookies.push(cookiesService.formatCookie('binding', rawClick.landingId, { 
        name: landingName,
        ttl: cookiesTtl 
      }));
    }

    // 5. Offer binding cookie
    if (payload.cookieBindOffer && rawClick.offerId) {
      const offerName = `offer_${rawClick.campaignId}`;
      payload.cookies.push(cookiesService.formatCookie('binding', rawClick.offerId, { 
        name: offerName,
        ttl: cookiesTtl 
      }));
    }

    if (payload.cookies.length > 0) {
      payload.log(`Buffered ${payload.cookies.length} tracking cookies via cookiesService`);
    }

    return {
      success: true,
      payload
    };
  }
}

