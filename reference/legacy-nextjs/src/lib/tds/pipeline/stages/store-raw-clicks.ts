/**
 * StoreRawClicksStage
 * 
 * Stores raw click data to database.
 * Based on Keitaro's StoreRawClicksStage.php
 * 
 * Storage logic:
 * 1. Check if click collection is enabled for stream
 * 2. Check if stats are disabled globally
 * 3. Store click to database
 */

import type { StageInterface, StageResult, RawClick, PipelinePayload } from '../types';

import { db } from '@/lib/db';

export class StoreRawClicksStage implements StageInterface {
  name = 'StoreRawClicksStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const stream = payload.getStream();
    const rawClick = payload.getRawClick();

    // Get clicks to store (could be multiple in batch operations)
    const clicksToStore = payload.getRawClicksToStore?.() || (rawClick ? [rawClick] : []);

    if (clicksToStore.length === 0) {
      payload.log('No clicks to store');
      return { success: true, payload };
    }

    payload.log(`Saving ${clicksToStore.length} click(s)`);

    // Check if stream has click collection disabled
    if (stream && !stream.collectClicks) {
      payload.log('Stream has click collection disabled. Skipping.');
      return { success: true, payload };
    }

    // Check if stats are disabled globally
    const statsDisabled = await this.isStatsDisabled();
    if (statsDisabled) {
      payload.log('Statistics disabled. Skipping.');
      return { success: true, payload };
    }

    // Store clicks
    let savedCount = 0;
    let errorCount = 0;

    for (const click of clicksToStore) {
      try {
        await this.saveClick(click);
        savedCount++;
      } catch (error) {
        console.error('Error saving click:', error);
        errorCount++;
      }
    }

    payload.log(`Saved ${savedCount} click(s), ${errorCount} error(s)`);

    return { success: true, payload };
  }

  /**
   * Check if statistics are disabled
   */
  private async isStatsDisabled(): Promise<boolean> {
    try {
      const setting = await db.setting.findUnique({
        where: { key: 'disable_stats' }
      });
      return setting?.value === 'true' || setting?.value === '1';
    } catch {
      return false;
    }
  }

  /**
   * Save click to database
   */
  private async saveClick(rawClick: RawClick): Promise<void> {
    // Check if click already exists
    const existingClick = await db.click.findUnique({
      where: { clickId: rawClick.clickId }
    });

    if (existingClick) {
      // Update existing click
      await db.click.update({
        where: { clickId: rawClick.clickId },
        data: {
          campaignId: rawClick.campaignId,
          streamId: rawClick.streamId,
          landingId: rawClick.landingId,
          offerId: rawClick.offerId,
          destinationUrl: rawClick.destination,
          landingUrl: rawClick.landingUrl,
          landingClicked: rawClick.landingClicked || false,
          landingClickedAt: rawClick.landingClickedAt,
          isBot: rawClick.isBot,
          botReason: rawClick.botReason,
          botType: rawClick.botType,
          isUsingProxy: rawClick.isUsingProxy,
          isUniqueCampaign: rawClick.isUniqueCampaign,
          isUniqueStream: rawClick.isUniqueStream,
          isUniqueGlobal: rawClick.isUniqueGlobal,
          isLead: rawClick.isLead,
          isSale: rawClick.isSale,
          isRejected: rawClick.isRejected,
          leadRevenue: rawClick.leadRevenue ?? undefined,
          saleRevenue: rawClick.saleRevenue ?? undefined,
          rejectedRevenue: rawClick.rejectedRevenue ?? undefined,
          cost: rawClick.cost ?? undefined

        }
      });
    } else {
      // Create new click
      await db.click.create({
        data: {
          clickId: rawClick.clickId,
          visitorCode: rawClick.visitorCode,
          campaignId: rawClick.campaignId,
          streamId: rawClick.streamId,
          landingId: rawClick.landingId,
          offerId: rawClick.offerId,
          parentCampaignId: rawClick.parentCampaignId,
          
          // Request data
          ip: rawClick.ip,
          userAgent: rawClick.userAgent,
          referrer: rawClick.referrer,
          language: rawClick.language,
          
          // Geo data
          country: rawClick.country,
          region: rawClick.region,
          city: rawClick.city,
          isp: rawClick.isp,
          operator: rawClick.operator,
          connectionType: rawClick.connectionType,
          
          // Device data
          deviceType: rawClick.deviceType,
          deviceModel: rawClick.deviceModel,
          deviceBrand: rawClick.deviceBrand,
          browser: rawClick.browser,
          browserVersion: rawClick.browserVersion,
          os: rawClick.os,
          osVersion: rawClick.osVersion,
          isMobile: rawClick.isMobile,
          
          // Traffic source params
          source: rawClick.source,
          keyword: rawClick.keyword,
          sub1: rawClick.subId1,
          sub2: rawClick.subId2,
          sub3: rawClick.subId3,
          sub4: rawClick.subId4,
          sub5: rawClick.subId5,
          extraParam1: rawClick.extraParam1,
          extraParam2: rawClick.extraParam2,
          extraParam3: rawClick.extraParam3,
          
          // Tracking IDs
          creativeId: rawClick.creativeId,
          adCampaignId: rawClick.adCampaignId,
          externalId: rawClick.externalId,
          xRequestedWith: rawClick.xRequestedWith,
          
          // Destination
          destinationUrl: rawClick.destination,
          landingUrl: rawClick.landingUrl,
          
          // Cloaking
          isBot: rawClick.isBot,
          botReason: rawClick.botReason,
          botType: rawClick.botType,
          isUsingProxy: rawClick.isUsingProxy,
          
          // Uniqueness
          isUniqueCampaign: rawClick.isUniqueCampaign ?? true,
          isUniqueStream: rawClick.isUniqueStream ?? true,
          isUniqueGlobal: rawClick.isUniqueGlobal ?? true,
          
          // Revenue
          isLead: rawClick.isLead ?? false,
          isSale: rawClick.isSale ?? false,
          isRejected: rawClick.isRejected ?? false,
          leadRevenue: rawClick.leadRevenue ?? 0,
          saleRevenue: rawClick.saleRevenue ?? 0,
          rejectedRevenue: rawClick.rejectedRevenue ?? 0,
          cost: rawClick.cost ?? 0,
          
          // Session
          sessionId: rawClick.sessionId,
          token: rawClick.token
        }
      });
    }
  }
}
