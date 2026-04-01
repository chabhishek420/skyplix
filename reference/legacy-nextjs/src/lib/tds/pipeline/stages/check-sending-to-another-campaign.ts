/**
 * CheckSendingToAnotherCampaignStage
 * 
 * Handles redirect to another campaign (ToCampaign action).
 * Based on Keitaro's CheckSendingToAnotherCampaign.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';


export class CheckSendingToAnotherCampaignStage implements StageInterface {
  name = 'CheckSendingToAnotherCampaignStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    // Check if action type is 'to_campaign'
    if (payload.actionType !== 'to_campaign') {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const actionPayload = payload.actionPayload;

    if (!actionPayload) {
      payload.log('ToCampaign action has no target campaign ID');
      return {
        success: true,
        payload
      };
    }

    // Set the forced campaign ID for next pipeline iteration
    payload.setForcedCampaignId(actionPayload);

    // Store parent campaign info for tracking
    if (rawClick && payload.campaign) {
      rawClick.parentCampaignId = payload.campaign.id;
      rawClick.parentSubId = rawClick.clickId;
    }

    // Abort current pipeline to restart with new campaign
    payload.abort();
    payload.log(`Redirecting to another campaign: ${actionPayload}`);

    return {
      success: true,
      payload,
      abort: true
    };
  }
}
