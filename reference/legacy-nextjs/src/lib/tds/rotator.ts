/**
 * Stream Rotator
 * Handles stream selection logic based on campaign type
 * Based on Keitaro TDS StreamRotator
 */

import type { Stream, Campaign, RawClick } from './pipeline/types';

/**
 * Association item for landing/offer selection
 */
export interface AssociationItem {
  id: string;
  entityId: string;
  share: number;
  status: string;
}

/**
 * Check result for stream filters
 */
interface FilterCheckResult {
  passed: boolean;
  reason?: string;
}

/**
 * Filter checker interface
 */
interface StreamFilterChecker {
  check(stream: Stream, rawClick: RawClick): FilterCheckResult;
}

/**
 * Stream Rotator
 * Implements position-based and weight-based stream selection
 */
export class StreamRotator {
  private campaign: Campaign;
  private rawClick: RawClick;
  private log: string[] = [];
  private filterChecker: StreamFilterChecker | null = null;

  constructor(campaign: Campaign, rawClick: RawClick) {
    this.campaign = campaign;
    this.rawClick = rawClick;
  }

  /**
   * Set filter checker
   */
  setFilterChecker(checker: StreamFilterChecker): this {
    this.filterChecker = checker;
    return this;
  }

  /**
   * Get log entries
   */
  getLog(): string[] {
    return this.log;
  }

  /**
   * Add log entry
   */
  private addLog(message: string): void {
    this.log.push(`[${new Date().toISOString()}] ${message}`);
  }

  /**
   * Choose stream by position (for forced streams)
   * Iterates through streams in order, returns first matching stream
   */
  chooseByPosition(streams: Stream[]): Stream | null {
    if (!streams || streams.length === 0) {
      return null;
    }

    // Sort by position
    const sorted = [...streams].sort((a, b) => (a.position || 0) - (b.position || 0));

    for (const stream of sorted) {
      if (this.checkFilters(stream)) {
        this.addLog(`Position match: Stream #${stream.id} at position ${stream.position}`);
        return stream;
      }
    }

    return null;
  }

  /**
   * Choose stream by weight (for regular streams)
   * Uses weighted random selection
   */
  chooseByWeight(streams: Stream[]): Stream | null {
    if (!streams || streams.length === 0) {
      return null;
    }

    // Check for visitor binding
    if (this.campaign.bindVisitors) {
      const boundStream = this.findBoundStream(streams);
      if (boundStream) {
        this.addLog(`Found bound stream #${boundStream.id}`);
        return boundStream;
      }
    }

    // Roll dice for weighted selection
    return this.rollDice(streams);
  }

  /**
   * Roll dice for weighted random selection
   * Algorithm from Keitaro:
   * 1. Shuffle streams
   * 2. Calculate total weight
   * 3. Pick random number 0 to totalWeight-1
   * 4. Select stream where random falls in weight range
   * 5. If filter fails, remove stream and retry
   */
  private rollDice(streams: Stream[]): Stream | null {
    if (streams.length === 0) {
      return null;
    }

    // Shuffle streams for randomness
    const shuffled = [...streams].sort(() => Math.random() - 0.5);

    // Calculate total weight
    let totalWeight = 0;
    for (const stream of shuffled) {
      totalWeight += stream.weight || 0;
    }

    if (totalWeight === 0) {
      this.addLog('Total weight is 0, cannot select stream');
      return null;
    }

    // Pick random number
    const rand = Math.floor(Math.random() * totalWeight);

    // Find stream
    let currentWeight = 0;
    for (const stream of shuffled) {
      const weight = stream.weight || 0;
      if (rand >= currentWeight && rand < currentWeight + weight) {
        if (this.checkFilters(stream)) {
          this.addLog(`Weight match: Stream #${stream.id} (weight ${weight}, rand ${rand})`);
          return stream;
        }
        // Filter failed, remove and retry
        const remaining = shuffled.filter(s => s.id !== stream.id);
        return this.rollDice(remaining);
      }
      currentWeight += weight;
    }

    return null;
  }

  /**
   * Find bound stream (for visitor binding feature)
   * Checks if visitor was previously bound to a stream
   */
  private findBoundStream(streams: Stream[]): Stream | null {
    // Check if visitor has a bound stream ID stored
    // In Keitaro, this uses Redis or cookie storage
    // For now, we'll implement a simple version
    
    if (!this.rawClick.visitorCode) {
      return null;
    }

    // TODO: Implement actual binding storage
    // For now, return null (no binding)
    return null;
  }

  /**
   * Check if stream passes all filters
   */
  private checkFilters(stream: Stream): boolean {
    if (!this.filterChecker) {
      return true;
    }

    const result = this.filterChecker.check(stream, this.rawClick);
    if (!result.passed) {
      this.addLog(`Stream #${stream.id} filter failed: ${result.reason || 'unknown'}`);
    }
    return result.passed;
  }
}

/**
 * Landing/Offer Rotator
 * Similar to StreamRotator but for landing pages and offers
 */
export class LandingOfferRotator {
  private log: string[] = [];
  private type: 'landing' | 'offer';
  private campaign: Campaign | null;
  private rawClick: RawClick | null;

  constructor(type: 'landing' | 'offer', campaign?: Campaign, rawClick?: RawClick) {
    this.type = type;
    this.campaign = campaign || null;
    this.rawClick = rawClick || null;
  }

  /**
   * Get log entries
   */
  getLogs(): string[] {
    return this.log;
  }

  /**
   * Add log entry
   */
  private addLog(message: string): void {
    this.log.push(`[${new Date().toISOString()}] ${message}`);
  }

  /**
   * Select from associations
   * Used for landing/offer selection from stream associations
   */
  selectFromAssociations<T extends { id: string }>(
    associations: AssociationItem[],
    entities: T[]
  ): T | null {
    if (!associations || associations.length === 0) {
      this.addLog('No associations to select from');
      return null;
    }

    // Filter active associations
    const active = associations.filter(a => a.status === 'active');
    if (active.length === 0) {
      this.addLog('No active associations');
      return null;
    }

    // Calculate total share
    let totalShare = 0;
    for (const assoc of active) {
      totalShare += assoc.share || 0;
    }

    if (totalShare === 0) {
      // If all shares are 0, distribute equally
      totalShare = active.length;
    }

    // Weighted random selection
    const rand = Math.random() * totalShare;
    let currentShare = 0;

    for (const assoc of active) {
      const share = assoc.share || 1;
      if (rand >= currentShare && rand < currentShare + share) {
        // Find matching entity
        const entity = entities.find(e => e.id === assoc.entityId);
        if (entity) {
          this.addLog(`Selected ${this.type} #${entity.id} (share ${share})`);
          return entity;
        }
      }
      currentShare += share;
    }

    // Fallback: return first matching entity
    for (const assoc of active) {
      const entity = entities.find(e => e.id === assoc.entityId);
      if (entity) {
        return entity;
      }
    }

    return null;
  }

  /**
   * Get random item by weight
   */
  getRandom<T extends { id: string; weight: number; status: string }>(
    items: T[]
  ): T | null {
    if (!items || items.length === 0) {
      return null;
    }

    // Filter active items
    const active = items.filter(item => item.status === 'active');
    if (active.length === 0) {
      return null;
    }

    // Calculate total weight
    let totalWeight = 0;
    for (const item of active) {
      totalWeight += item.weight || 0;
    }

    if (totalWeight === 0) {
      // If all weights are 0, pick random
      const idx = Math.floor(Math.random() * active.length);
      return active[idx];
    }

    // Weighted random selection
    const rand = Math.floor(Math.random() * totalWeight);
    let currentWeight = 0;

    for (const item of active) {
      const weight = item.weight || 0;
      if (rand >= currentWeight && rand < currentWeight + weight) {
        this.addLog(`Selected item #${item.id} (weight ${weight})`);
        return item;
      }
      currentWeight += weight;
    }

    return active[0];
  }
}
