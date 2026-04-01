/**
 * DomainRedirectStage
 * 
 * Handles domain-level redirects before processing click.
 * Based on Keitaro's DomainRedirectStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { db } from '@/lib/db';

export class DomainRedirectStage implements StageInterface {
  name = 'DomainRedirectStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.request) {
      return {
        success: false,
        payload,
        error: 'Empty request',
        abort: true
      };
    }

    const request = payload.request;
    const url = new URL(request.url);
    const hostname = url.hostname;

    try {
      // Check if there's a campaign associated with this domain
      const domainCampaign = await db.campaign.findFirst({
        where: {
          OR: [
            { alias: hostname },
            { token: hostname }
          ],
          status: 'active'
        }
      });

      if (domainCampaign) {
        payload.log(`Domain redirect: Found campaign ${domainCampaign.id} for domain ${hostname}`);
        payload.setForcedCampaignId(domainCampaign.id);
      }
    } catch (error) {
      payload.log(`Domain redirect check failed: ${error}`);
    }

    return {
      success: true,
      payload
    };
  }
}
