/**
 * ChooseOfferStage
 * 
 * Selects offer for traffic routing.
 * Based on Keitaro's ChooseOfferStage.php
 * 
 * Selection logic:
 * 1. Check if stream schema is 'landings' or 'offers'
 * 2. Check for forced offer selection
 * 3. Check for visitor binding (previous offer selection)
 * 4. Weighted random selection from associations
 * 5. Check conversion capacity (fallback to alternative offer)
 */

import type { StageInterface, StageResult, Stream, Offer, Campaign, RawClick, PipelinePayload } from '../types';

import { db } from '@/lib/db';
import { LandingOfferRotator, type AssociationItem } from '../../rotator';

// Exit parameter to skip offer selection
const IGNORE_OFFER_PARAM = 'exit';

export class ChooseOfferStage implements StageInterface {
  name = 'ChooseOfferStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const stream = payload.getStream();
    const campaign = payload.getCampaign();
    const rawClick = payload.getRawClick();
    const landing = payload.getLanding();

    // Skip if no stream
    if (!stream) {
      payload.log('No stream, skip choosing offer');
      return { success: true, payload };
    }

    // Skip if schema is not landings or offers
    if (stream.schema !== 'landings' && stream.schema !== 'offers') {
      payload.log(`Schema is '${stream.schema}', offer is not needed`);
      return { success: true, payload };
    }

    // Check required entities
    if (!campaign) {
      payload.log('No campaign, skip choosing offer');
      return { success: true, payload };
    }

    if (!rawClick) {
      payload.log('No rawClick, skip choosing offer');
      return { success: true, payload };
    }

    // Skip if landing is chosen and not forcing offer selection
    if (landing && !payload.isForceChooseOffer?.()) {
      payload.log('Landing is chosen, skip choosing offer');
      return { success: true, payload };
    }

    // Check for forced offer
    const forcedOfferId = payload.getForcedOfferId();
    if (forcedOfferId) {
      const hasOffer = await this.streamHasOffer(stream.id, forcedOfferId);
      if (hasOffer) {
        payload.log(`Loading forced offer: ${forcedOfferId}`);
        const offer = await this.getOffer(forcedOfferId);
        if (offer) {
          this.updatePayload(payload, offer, rawClick);
          return { success: true, payload };
        }
      }
    }

    // Get offer associations for this stream
    const associations = await this.getOfferAssociations(stream.id);
    
    if (associations.length === 0) {
      payload.log('No offers in the stream');
      return { success: true, payload };
    }

    // Get offer entities
    const offerIds = associations.map(a => a.entityId);
    const offers = await this.getOffers(offerIds);

    // Use rotator to select offer
    const rotator = new LandingOfferRotator('offer', campaign, rawClick);
    let selectedOffer = rotator.selectFromAssociations(associations, offers);

    // Log rotator messages
    rotator.getLogs().forEach(log => payload.log(log));

    if (!selectedOffer) {
      payload.log('Rotator returned empty result');
      return { success: true, payload };
    }

    // Check conversion capacity
    const availableOffer = await this.findAvailableOffer(selectedOffer);
    if (availableOffer && availableOffer.id !== selectedOffer.id) {
      payload.log(`Offer ${selectedOffer.id} reached conversion capacity. Alternative: ${availableOffer.id}`);
      selectedOffer = availableOffer;
    }

    if (!selectedOffer) {
      payload.log('No available offer found');
      return { success: true, payload };
    }

    payload.log(`Offer ${selectedOffer.id} is chosen`);

    // Check exit parameter
    if (payload.getParam(IGNORE_OFFER_PARAM) !== '1') {
      this.updatePayload(payload, selectedOffer, rawClick);
    }

    // Set need token for tracking
    payload.setNeedToken(true);

    // Handle force redirect to offer
    if (payload.isForceRedirectOffer?.()) {
      if (selectedOffer.actionPayload) {
        payload.setAction(
          (selectedOffer.actionType as any) || 'http_redirect',
          selectedOffer.actionPayload,
          selectedOffer.actionOptions || undefined
        );
      } else if (selectedOffer.url) {
        payload.setAction('http_redirect', selectedOffer.url);
      }
    }

    return { success: true, payload };
  }

  /**
   * Get offer associations for stream
   */
  private async getOfferAssociations(streamId: string): Promise<AssociationItem[]> {
    try {
      const associations = await db.streamOfferAssociation.findMany({
        where: {
          streamId: streamId,
          status: 'active'
        }
      });

      return associations.map(a => ({
        id: a.id,
        entityId: a.offerId,
        share: a.share,
        status: a.status
      }));
    } catch (error) {
      console.error('Error getting offer associations:', error);
      return [];
    }
  }

  /**
   * Check if stream has specific offer
   */
  private async streamHasOffer(streamId: string, offerId: string): Promise<boolean> {
    try {
      const count = await db.streamOfferAssociation.count({
        where: {
          streamId: streamId,
          offerId: offerId,
          status: 'active'
        }
      });
      return count > 0;
    } catch (error) {
      console.error('Error checking stream offer:', error);
      return false;
    }
  }

  /**
   * Get offer by ID
   */
  private async getOffer(offerId: string): Promise<Offer | null> {
    try {
      const offer = await db.offer.findUnique({
        where: { id: offerId }
      });

      if (!offer || offer.status !== 'active') {
        return null;
      }

      return this.mapOffer(offer);
    } catch (error) {
      console.error('Error getting offer:', error);
      return null;
    }
  }

  /**
   * Get multiple offers by IDs
   */
  private async getOffers(offerIds: string[]): Promise<Offer[]> {
    try {
      const offers = await db.offer.findMany({
        where: {
          id: { in: offerIds },
          status: 'active'
        }
      });

      return offers.map(o => this.mapOffer(o));
    } catch (error) {
      console.error('Error getting offers:', error);
      return [];
    }
  }

  /**
   * Map database offer to Offer type
   */
  private mapOffer(offer: any): Offer {
    return {
      id: offer.id,
      streamId: '',
      name: offer.name,
      url: offer.url || '',
      affiliateNetworkId: offer.affiliateNetworkId,
      payout: offer.payoutValue,
      weight: 100,
      status: offer.status as 'active' | 'paused',
      actionType: offer.actionType,
      actionPayload: offer.actionPayload,
      actionOptions: offer.actionOptions ? JSON.parse(offer.actionOptions) : null,
      payoutCurrency: offer.payoutCurrency,
      payoutType: offer.payoutType,
      country: offer.country,
      dailyCap: offer.dailyCap,
      alternativeOfferId: offer.alternativeOfferId
    };
  }

  /**
   * Find available offer (check conversion capacity)
   */
  private async findAvailableOffer(offer: Offer): Promise<Offer | null> {
    // If offer has no conversion cap, return as-is
    if (!offer.dailyCap) {
      return offer;
    }

    // Check daily conversion count
    try {
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      const conversionCount = await db.conversion.count({
        where: {
          offerId: offer.id,
          status: 'approved',
          createdAt: {
            gte: today
          }
        }
      });

      if (conversionCount < (offer.dailyCap || 0)) {
        return offer;
      }

      // Cap reached, check for alternative offer
      if (offer.alternativeOfferId) {
        const alternativeOffer = await this.getOffer(offer.alternativeOfferId);
        if (alternativeOffer) {
          return this.findAvailableOffer(alternativeOffer);
        }
      }

      return null;
    } catch (error) {
      console.error('Error checking conversion capacity:', error);
      return offer;
    }
  }

  /**
   * Update payload with offer selection
   */
  private updatePayload(payload: PipelinePayload, offer: Offer, rawClick: RawClick): void {
    // Update raw click
    rawClick.offerId = offer.id;

    // Set offer in payload
    payload.setOffer(offer);

    // Set payout if available
    if (offer.payout) {
      rawClick.cost = offer.payout;
    }
  }
}
