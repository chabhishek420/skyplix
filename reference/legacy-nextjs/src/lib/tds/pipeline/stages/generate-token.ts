/**
 * GenerateTokenStage
 * 
 * Generates LP token for landing page to offer tracking.
 * Based on Keitaro's GenerateTokenStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import crypto from 'crypto';

export class GenerateTokenStage implements StageInterface {
  name = 'GenerateTokenStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick || !payload.campaign) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const campaign = payload.campaign;
    const landing = payload.landing;
    const offer = payload.offer;

    // Check if token is needed (landing -> offer flow)
    const needToken = landing && offer;
    payload.setNeedToken(needToken);

    if (needToken) {
      // Generate LP token
      const token = this.generateLpToken(rawClick, campaign, landing, offer);
      rawClick.token = token;
      payload.log(`Generated LP token: ${token}`);
    }

    return {
      success: true,
      payload
    };
  }

  /**
   * Generate LP token for tracking landing page to offer transitions
   */
  private generateLpToken(
    rawClick: any,
    campaign: any,
    landing: any,
    offer: any
  ): string {
    const data = {
      clickId: rawClick.clickId,
      campaignId: campaign.id,
      landingId: landing?.id,
      offerId: offer?.id,
      timestamp: Date.now()
    };

    // Create token from JSON encoded data
    const json = JSON.stringify(data);
    const token = Buffer.from(json).toString('base64url');
    
    return token;
  }
}
