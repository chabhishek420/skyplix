/**
 * UpdateCampaignUniquenessSessionStage
 * 
 * Checks and updates campaign-level uniqueness.
 * Based on Keitaro's UpdateCampaignUniquenessSessionStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { cookiesService } from '../../services/cookies-service';


export class UpdateCampaignUniquenessSessionStage implements StageInterface {
  name = 'UpdateCampaignUniquenessSessionStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick || !payload.campaign) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const campaign = payload.campaign;

    // Check uniqueness based on visitor code and campaign
    if (payload.request && rawClick.visitorCode && campaign.bindVisitors) {
      const isUnique = cookiesService.isUniqueForCampaign(payload.request, rawClick.visitorCode, campaign.id.toString());
      rawClick.isUniqueCampaign = isUnique;
      
      if (!isUnique) {
        payload.log(`Campaign uniqueness check: NOT unique (visitor already has cookie for campaign ${campaign.id})`);
      } else {
        payload.log(`Campaign uniqueness check: UNIQUE (visitor: ${rawClick.visitorCode})`);
      }
    } else {
      // Default to unique if no binding or no request context
      rawClick.isUniqueCampaign = true;
    }


    return {
      success: true,
      payload
    };
  }
}
