/**
 * CheckDefaultCampaignStage
 * 
 * If no campaign found, check for default campaign fallback.
 * Based on Keitaro's CheckDefaultCampaignStage.php
 */

import type { StageInterface, StageResult, Campaign, PipelinePayload } from '../types';

import { db } from '@/lib/db';

export class CheckDefaultCampaignStage implements StageInterface {
  name = 'CheckDefaultCampaignStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    // If campaign already set, skip
    if (payload.campaign) {
      return {
        success: true,
        payload
      };
    }

    // If forced campaign ID is set, skip
    if (payload.forcedCampaignId) {
      return {
        success: true,
        payload
      };
    }

    try {
      // Find default campaign (first active campaign)
      const defaultCampaign = await db.campaign.findFirst({
        where: {
          status: 'active'
        },
        orderBy: {
          createdAt: 'asc'
        }
      });

      if (defaultCampaign) {
        const campaign: Campaign = {
          id: defaultCampaign.id,
          campaignId: defaultCampaign.campaignId,
          name: defaultCampaign.name,
          alias: defaultCampaign.alias || undefined,
          status: defaultCampaign.status as 'active' | 'paused' | 'deleted',
          type: (defaultCampaign.type as 'position' | 'weight') || 'weight',
          redirectType: 'http302',
          cookiesTtl: defaultCampaign.cookiesTtl,
          bindVisitors: defaultCampaign.bindVisitors,
          bindVisitorsLanding: defaultCampaign.bindVisitorsLanding,
          bindVisitorsOffer: defaultCampaign.bindVisitorsOffer,
          cloakingEnabled: false,
          safePageUrl: defaultCampaign.safePageUrl,
          destinationUrl: defaultCampaign.destinationUrl,
          offerId: defaultCampaign.offerId,
          affiliateId: defaultCampaign.affiliateId || undefined,
          token: defaultCampaign.token || undefined
        };

        payload.setCampaign(campaign);
        payload.log(`Using default campaign: ${campaign.name} (${campaign.id})`);
      }
    } catch (error) {
      payload.log(`Error finding default campaign: ${error}`);
    }

    return {
      success: true,
      payload
    };
  }
}
