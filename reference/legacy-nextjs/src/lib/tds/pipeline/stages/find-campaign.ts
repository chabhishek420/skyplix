/**
 * FindCampaignStage
 * 
 * Finds the campaign based on request parameters.
 * Based on Keitaro's FindCampaignStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { db } from '@/lib/db';

export class FindCampaignStage implements StageInterface {
  name = 'FindCampaignStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const rawClick = payload.getRawClick();
    if (!rawClick) {
      return {
        success: false,
        payload,
        error: 'RawClick not initialized',
        abort: true
      };
    }

    const params = payload.getAllParams();
    
    // Get campaign ID - check forced campaign ID first (for campaign redirects)
    let campaignId: string | null = null;
    
    // Check if forced campaign ID is set (from ToCampaign action)
    const forcedCampaignId = payload.forcedCampaignId;
    if (forcedCampaignId) {
      campaignId = forcedCampaignId;
      payload.log(`Using forced campaign ID: ${forcedCampaignId}`);
      // Clear forced campaign ID after use
      payload.forcedCampaignId = null;
    }
    
    // Check various parameter names
    if (!campaignId) {
      campaignId = params.campaign_id || params.campaign || params.camp_id || params.cid;
    }
    
    // Check token parameter (encoded campaign info)
    if (!campaignId && params._token) {
      const decoded = this.decodeToken(params._token);
      if (decoded) {
        campaignId = decoded.campaignId;
        if (decoded.streamId) payload.setForcedStreamId(decoded.streamId);
        if (decoded.landingId) payload.setForcedLandingId(decoded.landingId);
        if (decoded.offerId) payload.setForcedOfferId(decoded.offerId);
      }
    }
    
    // Check for domain-based campaign matching
    if (!campaignId && payload.request) {
      const host = payload.request.headers.get('host');
      if (host) {
        const domainCampaign = await this.findCampaignByDomain(host);
        if (domainCampaign) {
          campaignId = domainCampaign.campaignId.toString();
        }
      }
    }

    if (!campaignId) {
      payload.setBody('INVALID_CAMPAIGN_ID', 'text/plain');
      payload.abort();
      return {
        success: false,
        payload,
        error: 'INVALID_CAMPAIGN_ID',
        abort: true
      };
    }

    // Find campaign in database
    const campaign = await this.findCampaign(parseInt(campaignId));
    
    if (!campaign) {
      payload.setBody('INVALID_OFFER_ID', 'text/plain');
      payload.abort();
      return {
        success: false,
        payload,
        error: 'INVALID_OFFER_ID',
        abort: true
      };
    }

    if (campaign.status !== 'active') {
      payload.setBody('ADV_INACTIVE', 'text/plain');
      payload.abort();
      return {
        success: false,
        payload,
        error: 'ADV_INACTIVE',
        abort: true
      };
    }

    payload.setCampaign(campaign);
    rawClick.campaignId = campaign.id;
    payload.log(`Found campaign: ${campaign.name} (${campaign.campaignId})`);

    return {
      success: true,
      payload
    };
  }

  /**
   * Find campaign by ID
   */
  private async findCampaign(campaignId: number) {
    try {
      const campaign = await db.campaign.findFirst({
        where: { 
          campaignId: campaignId,
          status: 'active'
        },
        include: {
          trafficSources: {
            where: { status: 'active' },
            include: {
              trafficSource: true
            },
            take: 1
          }
        }
      });

      if (!campaign) return null;

      // Extract traffic source params if available
      const campaignTrafficSource = campaign.trafficSources?.[0];
      const ts = campaignTrafficSource?.trafficSource;

      return {
        id: campaign.id,
        campaignId: campaign.campaignId,
        name: campaign.name,
        status: campaign.status as any,
        type: 'weight' as const, // default to weight-based
        redirectType: 'http302' as const,
        cookiesTtl: campaign.cookiesTtl || 24,
        bindVisitors: campaign.bindVisitors,
        bindVisitorsLanding: campaign.bindVisitorsLanding,
        bindVisitorsOffer: campaign.bindVisitorsOffer,
        cloakingEnabled: true,
        safePageUrl: campaign.safePageUrl,
        destinationUrl: campaign.destinationUrl,
        offerId: campaign.offerId,
        affiliateId: campaign.affiliateId,
        trafficSource: ts ? {
          keywordParam: ts.keywordParam,
          costParam: ts.costParam,
          sourceParam: ts.sourceParam,
        } : undefined
      };
    } catch (error) {
      console.error('Error finding campaign:', error);
      return null;
    }
  }


  /**
   * Find campaign by domain
   */
  private async findCampaignByDomain(domain: string): Promise<{ campaignId: number } | null> {
    // In production, this would query a domain_campaigns table
    // For now, return null
    return null;
  }

  /**
   * Decode token parameter
   */
  private decodeToken(token: string): { campaignId: string; streamId?: string; landingId?: string; offerId?: string } | null {
    try {
      // Token format: base64 encoded JSON
      const decoded = Buffer.from(token, 'base64').toString('utf-8');
      return JSON.parse(decoded);
    } catch {
      return null;
    }
  }
}
