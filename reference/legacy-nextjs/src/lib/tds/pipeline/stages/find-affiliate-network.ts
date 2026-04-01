/**
 * FindAffiliateNetworkStage
 * 
 * Finds the affiliate network for an offer.
 * Based on Keitaro's FindAffiliateNetworkStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { db } from '@/lib/db';

export class FindAffiliateNetworkStage implements StageInterface {
  name = 'FindAffiliateNetworkStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick || !payload.offer) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const offer = payload.offer;

    // Get affiliate network ID from offer
    if (offer.affiliateNetworkId) {
      rawClick.affiliateNetworkId = offer.affiliateNetworkId;
      payload.log(`Affiliate network: ${offer.affiliateNetworkId}`);
    }

    return {
      success: true,
      payload
    };
  }
}
