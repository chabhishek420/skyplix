/**
 * ToCampaign Action
 * Redirect to another campaign
 * Based on Keitaro's ToCampaign.php action
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';
import { db } from '@/lib/db';

export class ToCampaignAction extends AbstractAction {
  name = 'to_campaign';
  
  /**
   * Execute to_campaign action
   * Redirects to another campaign
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    const options = this.options;
    
    if (!url && !options.campaignId) {
      return {
        success: false,
        payload: this.payload,
        error: 'No campaign specified for to_campaign action',
        statusCode: 400
      };
    }
    
    // Get campaign ID from options or URL
    let targetCampaignId: string | null = null;
    
    if (options.campaignId) {
      targetCampaignId = String(options.campaignId);
    } else if (url) {
      // Parse campaign ID from URL
      try {
        const urlObj = new URL(url);
        targetCampaignId = urlObj.searchParams.get('campaign_id');
      } catch {
        // URL might just be the campaign ID
        targetCampaignId = url;
      }
    }
    
    if (!targetCampaignId) {
      return {
        success: false,
        payload: this.payload,
        error: 'Could not determine target campaign ID',
        statusCode: 400
      };
    }
    
    // Verify campaign exists
    const campaignIdNum = parseInt(targetCampaignId, 10);
    if (isNaN(campaignIdNum)) {
      return {
        success: false,
        payload: this.payload,
        error: 'Invalid campaign ID',
        statusCode: 400
      };
    }
    
    const campaign = await db.campaign.findUnique({
      where: { campaignId: campaignIdNum }
    });
    
    if (!campaign) {
      return {
        success: false,
        payload: this.payload,
        error: 'Target campaign not found',
        statusCode: 404
      };
    }
    
    // Set forced campaign ID to trigger pipeline restart
    this.payload.setForcedCampaignId(campaign.id);
    
    return {
      success: true,
      payload: this.payload,
      statusCode: 302
    };
  }
}

export default ToCampaignAction;
