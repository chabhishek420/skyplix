/**
 * ChooseLandingStage
 * 
 * Selects landing page for traffic routing.
 * Based on Keitaro's ChooseLandingStage.php
 * 
 * Selection logic:
 * 1. Check if stream schema is 'landings' or 'offers'
 * 2. Check for forced landing selection
 * 3. Check for visitor binding (previous landing selection)
 * 4. Weighted random selection from associations
 */

import type { StageInterface, StageResult, Stream, Landing, Campaign, RawClick, PipelinePayload } from '../types';

import { db } from '@/lib/db';
import { LandingOfferRotator, type AssociationItem } from '../../rotator';

export class ChooseLandingStage implements StageInterface {
  name = 'ChooseLandingStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const stream = payload.getStream();
    const campaign = payload.getCampaign();
    const rawClick = payload.getRawClick();

    // Skip if no stream
    if (!stream) {
      payload.log('No stream, skip choosing landing');
      return { success: true, payload };
    }

    // Skip if schema is not landings or offers
    if (stream.schema !== 'landings' && stream.schema !== 'offers') {
      payload.log(`Schema is '${stream.schema}', skip choosing landings`);
      return { success: true, payload };
    }

    // Check required entities
    if (!campaign) {
      payload.log('No campaign, skip choosing landing');
      return { success: true, payload };
    }

    if (!rawClick) {
      payload.log('No rawClick, skip choosing landing');
      return { success: true, payload };
    }

    // Check if landing is already selected
    const currentLanding = payload.getLanding();
    if (currentLanding) {
      payload.log(`Landing is preselected: ${currentLanding.id}`);
      return { success: true, payload };
    }

    // Check for forced landing
    const forcedLandingId = payload.getForcedLandingId();
    if (forcedLandingId) {
      const landing = await this.getLanding(forcedLandingId);
      if (landing) {
        payload.log(`Using forced landing: ${forcedLandingId}`);
        this.updatePayload(payload, landing, rawClick);
        return { success: true, payload };
      }
    }

    // Get landing associations for this stream
    const associations = await this.getLandingAssociations(stream.id);
    
    if (associations.length === 0) {
      payload.log('No landings in the stream');
      return { success: true, payload };
    }

    // Get landing entities
    const landingIds = associations.map(a => a.entityId);
    const landings = await this.getLandings(landingIds);

    // Use rotator to select landing
    const rotator = new LandingOfferRotator('landing', campaign, rawClick);
    const selectedLanding = rotator.selectFromAssociations(associations, landings);

    // Log rotator messages
    rotator.getLogs().forEach(log => payload.log(log));

    if (!selectedLanding) {
      payload.log('No landing selected');
      return { success: true, payload };
    }

    payload.log(`Landing ${selectedLanding.id} is chosen`);
    this.updatePayload(payload, selectedLanding, rawClick);

    // Check if stream has offers (for LP -> Offer flow)
    const hasOffers = await this.streamHasOffers(stream.id);
    if (hasOffers) {
      payload.setNeedToken(true);
      payload.setAddTokenToUrl(true);
    }

    return { success: true, payload };
  }

  /**
   * Get landing associations for stream
   */
  private async getLandingAssociations(streamId: string): Promise<AssociationItem[]> {
    try {
      const associations = await db.streamLandingAssociation.findMany({
        where: {
          streamId: streamId,
          status: 'active'
        }
      });

      return associations.map(a => ({
        id: a.id,
        entityId: a.landingId,
        share: a.share,
        status: a.status
      }));
    } catch (error) {
      console.error('Error getting landing associations:', error);
      return [];
    }
  }

  /**
   * Get landing by ID
   */
  private async getLanding(landingId: string): Promise<Landing | null> {
    try {
      const landing = await db.landing.findUnique({
        where: { id: landingId }
      });

      if (!landing || landing.status !== 'active') {
        return null;
      }

      return {
        id: landing.id,
        streamId: '', // Not applicable here
        name: landing.name,
        url: landing.url || '',
        weight: 100, // Default weight
        status: landing.status as 'active' | 'paused',
        actionType: landing.actionType,
        actionPayload: landing.actionPayload,
        actionOptions: landing.actionOptions ? JSON.parse(landing.actionOptions) : null
      };
    } catch (error) {
      console.error('Error getting landing:', error);
      return null;
    }
  }

  /**
   * Get multiple landings by IDs
   */
  private async getLandings(landingIds: string[]): Promise<Landing[]> {
    try {
      const landings = await db.landing.findMany({
        where: {
          id: { in: landingIds },
          status: 'active'
        }
      });

      return landings.map(l => ({
        id: l.id,
        streamId: '',
        name: l.name,
        url: l.url || '',
        weight: 100,
        status: l.status as 'active' | 'paused',
        actionType: l.actionType,
        actionPayload: l.actionPayload,
        actionOptions: l.actionOptions ? JSON.parse(l.actionOptions) : null
      }));
    } catch (error) {
      console.error('Error getting landings:', error);
      return [];
    }
  }

  /**
   * Check if stream has offers
   */
  private async streamHasOffers(streamId: string): Promise<boolean> {
    try {
      const count = await db.streamOfferAssociation.count({
        where: {
          streamId: streamId,
          status: 'active'
        }
      });
      return count > 0;
    } catch (error) {
      console.error('Error checking stream offers:', error);
      return false;
    }
  }

  /**
   * Update payload with landing selection
   */
  private updatePayload(payload: PipelinePayload, landing: Landing, rawClick: RawClick): void {
    // Set action from landing
    if (landing.actionPayload) {
      payload.setAction(
        (landing.actionType as any) || 'http_redirect',
        landing.actionPayload,
        landing.actionOptions || undefined
      );
    } else if (landing.url) {
      payload.setAction('http_redirect', landing.url);
    }

    // Update raw click
    rawClick.landingId = landing.id;
    rawClick.landingUrl = landing.url;

    // Set landing in payload
    payload.setLanding(landing);
  }
}
