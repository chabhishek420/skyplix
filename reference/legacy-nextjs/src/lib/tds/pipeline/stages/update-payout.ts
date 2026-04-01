/**
 * UpdatePayoutStage
 * 
 * Updates click payout from offer settings.
 * Based on Keitaro's UpdatePayoutStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';


export class UpdatePayoutStage implements StageInterface {
  name = 'UpdatePayoutStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick || !payload.offer) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const offer = payload.offer;

    // Get payout from offer
    if (offer.payout) {
      (rawClick as any).payout = offer.payout;
      (rawClick as any).payoutCurrency = offer.payoutCurrency || 'USD';
      payload.log(`Offer payout: ${offer.payout} ${offer.payoutCurrency || 'USD'}`);
    }

    return {
      success: true,
      payload
    };
  }
}
