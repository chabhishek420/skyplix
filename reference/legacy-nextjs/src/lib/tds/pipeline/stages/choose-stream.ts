/**
 * ChooseStreamStage
 * 
 * Selects the stream for traffic routing.
 * Based on Keitaro's ChooseStreamStage.php and StreamRotator.php
 * 
 * Stream selection order:
 * 1. Forced stream ID from payload
 * 2. FORCED type streams (position-based filter matching)
 * 3. REGULAR type streams (position or weight based on campaign type)
 * 4. DEFAULT type stream (fallback)
 * 
 * CRITICAL: Weight-based selection uses recursive retry with post-selection filter check
 */

import type { StageInterface, StageResult, Stream, RawClick, Campaign, PipelinePayload } from '../types';

import { db } from '@/lib/db';
import { checkFilters, type StreamFilter } from '../../filters';
import { entityBindingService } from '../../services/entity-binding-service';

export class ChooseStreamStage implements StageInterface {
  name = 'ChooseStreamStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const campaign = payload.getCampaign();
    const rawClick = payload.getRawClick();

    if (!campaign) {
      return {
        success: false,
        payload,
        error: 'Campaign not set',
        abort: true
      };
    }

    if (!rawClick) {
      return {
        success: false,
        payload,
        error: 'RawClick not set',
        abort: true
      };
    }

    // Get streams for campaign, grouped by type
    const groupedStreams = await this.getGroupedStreams(campaign.id);
    
    if (!groupedStreams.forced.length && !groupedStreams.regular.length && !groupedStreams.default.length) {
      payload.log('No streams found for campaign');
      if (campaign.destinationUrl) {
        payload.setAction('http_redirect', campaign.destinationUrl);
        rawClick.destination = campaign.destinationUrl;
        return { success: true, payload };
      }
      
      payload.setAction('do_nothing', '');
      return { success: true, payload };
    }

    let selectedStream: Stream | null = null;

    // 1. Check for forced stream ID from payload
    const forcedStreamId = payload.getForcedStreamId();
    if (forcedStreamId) {
      selectedStream = this.findStreamById(groupedStreams, forcedStreamId);
      if (selectedStream) {
        payload.log(`Using forced stream ID: ${selectedStream.name}`);
      } else {
        payload.log(`Forced stream ID ${forcedStreamId} not found`);
      }
    }

    // 2. Try FORCED type streams (position-based)
    if (!selectedStream) {
      selectedStream = await this.chooseByPosition(groupedStreams.forced, payload, rawClick);
      if (selectedStream) {
        payload.log(`Selected forced stream: ${selectedStream.name}`);
      }
    }

    // 3. Try REGULAR type streams (based on campaign type)
    if (!selectedStream) {
      // Campaign type determines selection method for regular streams
      if (campaign.type === 'position') {
        selectedStream = await this.chooseByPosition(groupedStreams.regular, payload, rawClick);
      } else {
        selectedStream = await this.chooseByWeight(groupedStreams.regular, campaign, payload, rawClick);
        
        // Enable visitor binding if selected and campaign has it enabled
        if (selectedStream && campaign.bindVisitors) {
          payload.enableCookieBindStream();
        }
      }
      if (selectedStream) {
        payload.log(`Selected regular stream: ${selectedStream.name}`);
      }
    }

    // 4. Fall back to DEFAULT stream
    if (!selectedStream && groupedStreams.default.length > 0) {
      selectedStream = groupedStreams.default[0];
      payload.log(`Using default stream: ${selectedStream.name}`);
    }

    // No stream selected - do nothing
    if (!selectedStream) {
      payload.setAction('do_nothing', '');
      return { success: true, payload };
    }

    payload.setStream(selectedStream);
    rawClick.streamId = selectedStream.id;

    // Set action from stream (for non-landing/offer schemas)
    if (selectedStream.schema !== 'landings' && selectedStream.schema !== 'offers') {
      payload.setAction(
        (selectedStream.actionType || 'http_redirect') as any, 
        selectedStream.actionPayload || '',
        selectedStream.actionOptions || undefined
      );
      rawClick.destination = selectedStream.actionPayload;
    }

    return { success: true, payload };
  }

  /**
   * Get streams grouped by type
   */
  private async getGroupedStreams(campaignId: string): Promise<{
    forced: Stream[];
    regular: Stream[];
    default: Stream[];
  }> {
    const grouped = { forced: [] as Stream[], regular: [] as Stream[], default: [] as Stream[] };

    try {
      const dbStreams = await db.stream.findMany({
        where: { campaignId, status: 'active' },
        orderBy: [{ weight: 'desc' }, { createdAt: 'asc' }],
        include: { filters: true }
      });

      for (const s of dbStreams) {
        const stream: Stream = {
          id: s.id,
          campaignId: s.campaignId,
          name: s.name,
          type: this.getStreamType(s.type),
          schema: this.getStreamSchema(s.schema),
          actionType: s.actionType,
          actionPayload: s.actionPayload,
          actionOptions: s.actionOptions ? JSON.parse(s.actionOptions as string) : null,
          weight: s.weight,
          position: s.position || 0,
          status: s.status as 'active',
          collectClicks: s.collectClicks,
          filterOr: s.filterOr,
          filters: s.filters.map(f => ({
            id: f.id,
            streamId: f.streamId,
            name: f.name,
            mode: f.mode as 'accept' | 'reject',
            payload: f.payload ? JSON.parse(f.payload) : {}
          }))
        };

        if (stream.type === 'forced') grouped.forced.push(stream);
        else if (stream.type === 'default') grouped.default.push(stream);
        else grouped.regular.push(stream);
      }

      return grouped;
    } catch (error) {
      console.error('Error getting streams:', error);
      return grouped;
    }
  }

  /**
   * Find stream by ID across all groups
   */
  private findStreamById(grouped: { forced: Stream[]; regular: Stream[]; default: Stream[] }, id: string): Stream | null {
    const all = [...grouped.forced, ...grouped.regular, ...grouped.default];
    return all.find(s => s.id === id) || null;
  }

  /**
   * Choose stream by position (first matching wins)
   * Based on PHP: StreamRotator::chooseByPosition
   */
  private async chooseByPosition(
    streams: Stream[],
    payload: PipelinePayload,
    rawClick: RawClick
  ): Promise<Stream | null> {
    for (const stream of streams) {
      const filters = (stream as Stream & { filters?: StreamFilter[] }).filters;
      
      if (!filters || filters.length === 0) {
        return stream;
      }

      const result = checkFilters(filters, rawClick, stream.filterOr);
      if (result.passed) {
        payload.log(`Stream ${stream.name} passed filters`);
        return stream;
      }
      payload.log(`Stream ${stream.name} filtered out: ${result.reason}`);
    }

    return null;
  }

  /**
   * Choose stream by weight with recursive retry
   * CRITICAL: This matches Keitaro's StreamRotator::_rollDice algorithm
   * 
   * Algorithm:
   * 1. Check for bound stream first (if binding enabled)
   * 2. Shuffle streams for randomness
   * 3. Select by weighted random
   * 4. Check filter AFTER selection
   * 5. If filter fails, recursively retry with remaining streams
   */
  private async chooseByWeight(
    streams: Stream[],
    campaign: Campaign,
    payload: PipelinePayload,
    rawClick: RawClick
  ): Promise<Stream | null> {
    if (streams.length === 0) return null;

    // 1. Check for bound stream first if visitor binding enabled
    if (campaign.bindVisitors) {
      const boundStream = await this.findBoundStream(streams, campaign, rawClick);
      if (boundStream) {
        payload.log(`Found bound stream: ${boundStream.name}`);
        return boundStream;
      }
    }

    // 2. Roll dice with recursive retry
    return this.rollDice(streams, payload, rawClick);
  }

  /**
   * Roll dice for weighted random selection with recursive retry
   * Based on PHP: StreamRotator::_rollDice
   */
  private async rollDice(
    streams: Stream[],
    payload: PipelinePayload,
    rawClick: RawClick,
    depth: number = 0
  ): Promise<Stream | null> {
    // Prevent infinite recursion
    if (depth > 10 || streams.length === 0) {
      return null;
    }

    // Shuffle streams for randomness (critical for equal weights)
    const shuffled = this.shuffleArray([...streams]);

    // Calculate total weight
    let totalWeight = 0;
    for (const stream of shuffled) {
      totalWeight += stream.weight;
    }

    if (totalWeight === 0) {
      return null;
    }

    // Random selection (zero-indexed, matching PHP's mt_rand(0, totalWeight-1))
    const rand = Math.floor(Math.random() * totalWeight);
    let currentWeight = 0;
    let selectedIndex = 0;

    // Find selected stream by weight
    for (let i = 0; i < shuffled.length; i++) {
      const stream = shuffled[i];
      if (currentWeight <= rand && rand < currentWeight + stream.weight) {
        selectedIndex = i;
        
        // CRITICAL: Check filter AFTER selection (not before)
        const filters = (stream as Stream & { filters?: StreamFilter[] }).filters;
        
        if (!filters || filters.length === 0) {
          return stream;
        }

        const result = checkFilters(filters, rawClick, stream.filterOr);
        if (result.passed) {
          return stream;
        }

        // Filter failed - remove this stream and recursively retry
        payload.log(`Stream ${stream.name} failed filter, retrying with remaining streams`);
        const remaining = shuffled.filter((_, idx) => idx !== selectedIndex);
        return this.rollDice(remaining, payload, rawClick, depth + 1);
      }
      currentWeight += stream.weight;
    }

    return null;
  }

  /**
   * Find bound stream for visitor
   * Based on PHP: StreamRotator::_findBoundStream
   */
  private async findBoundStream(
    streams: Stream[],
    campaign: Campaign,
    rawClick: RawClick
  ): Promise<Stream | null> {
    const uniquenessId = this.getUniquenessId(rawClick.ipString || rawClick.ip, rawClick.userAgent);
    const boundStreamId = await entityBindingService.findBoundEntity(
      uniquenessId,
      campaign.id,
      'stream'
    );

    if (!boundStreamId) {
      return null;
    }

    return streams.find(s => s.id === boundStreamId) || null;
  }

  /**
   * Generate uniqueness ID (MD5 of IP + optional User-Agent)
   * Based on PHP: UniquenessSessionService::getUniquenessId
   */
  private getUniquenessId(ip: string, userAgent?: string): string {
    const data = ip + (userAgent || '');
    // Simple hash function (in production, use crypto.createHash('md5'))
    let hash = 0;
    for (let i = 0; i < data.length; i++) {
      const char = data.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash;
    }
    return Math.abs(hash).toString(16).padStart(8, '0');
  }

  /**
   * Fisher-Yates shuffle
   */
  private shuffleArray<T>(array: T[]): T[] {
    for (let i = array.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
  }

  /**
   * Convert database stream type to interface type
   */
  private getStreamType(dbType: string): 'forced' | 'regular' | 'default' {
    switch (dbType) {
      case 'forced': return 'forced';
      case 'default': return 'default';
      default: return 'regular';
    }
  }

  /**
   * Convert database schema to interface schema
   */
  private getStreamSchema(dbSchema: string): 'url' | 'landings' | 'offers' | 'action' {
    switch (dbSchema) {
      case 'landings': return 'landings';
      case 'offers': return 'offers';
      case 'action': return 'action';
      default: return 'url';
    }
  }
}
