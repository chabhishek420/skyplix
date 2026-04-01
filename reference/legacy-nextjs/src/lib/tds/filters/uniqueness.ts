/**
 * Uniqueness Filter
 * Filter by visitor uniqueness (stream, campaign, global)
 * Based on Keitaro's Uniqueness.php filter
 */

import type { RawClick } from '../pipeline/types';
import type { FilterInterface, FilterResult, StreamFilter } from './types';

// Uniqueness scope types
export const UNIQUE_STREAM = 'stream';
export const UNIQUE_CAMPAIGN = 'campaign';
export const UNIQUE_GLOBAL = 'global';

// In-memory session storage (in production, use Redis/Database)
interface SessionEntry {
  campaignIds: Set<string>;
  streamIds: Set<string>;
  lastSeen: Date;
}

const sessionStore = new Map<string, SessionEntry>();

/**
 * Get uniqueness ID from IP and optionally User-Agent
 */
export function getUniquenessId(ip: string, userAgent?: string): string {
  // Simple hash function (in production, use murmurhash3 like Keitaro)
  const data = ip + (userAgent || '');
  let hash = 0;
  for (let i = 0; i < data.length; i++) {
    const char = data.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32bit integer
  }
  return Math.abs(hash).toString(16);
}

/**
 * Check if visitor is unique for stream
 */
export function isUniqueForStream(ip: string, userAgent: string | undefined, streamId: string, campaignId: string): boolean {
  const uniquenessId = getUniquenessId(ip, userAgent);
  const session = sessionStore.get(uniquenessId);
  
  if (!session) {
    return true;
  }
  
  return !session.streamIds.has(streamId);
}

/**
 * Check if visitor is unique for campaign
 */
export function isUniqueForCampaign(ip: string, userAgent: string | undefined, campaignId: string): boolean {
  const uniquenessId = getUniquenessId(ip, userAgent);
  const session = sessionStore.get(uniquenessId);
  
  if (!session) {
    return true;
  }
  
  return !session.campaignIds.has(campaignId);
}

/**
 * Check if visitor is globally unique
 */
export function isUniqueGlobal(ip: string, userAgent: string | undefined): boolean {
  const uniquenessId = getUniquenessId(ip, userAgent);
  return !sessionStore.has(uniquenessId);
}

/**
 * Record visitor session
 */
export function recordSession(ip: string, userAgent: string | undefined, campaignId: string, streamId?: string): void {
  const uniquenessId = getUniquenessId(ip, userAgent);
  let session = sessionStore.get(uniquenessId);
  
  if (!session) {
    session = {
      campaignIds: new Set(),
      streamIds: new Set(),
      lastSeen: new Date()
    };
  }
  
  session.campaignIds.add(campaignId);
  if (streamId) {
    session.streamIds.add(streamId);
  }
  session.lastSeen = new Date();
  
  sessionStore.set(uniquenessId, session);
}

/**
 * Uniqueness Filter
 * Checks if visitor is unique based on configured scope
 */
export class UniquenessFilter implements FilterInterface {
  name = 'uniqueness';
  description = 'Filter by visitor uniqueness';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { scope?: string };
    const scope = payload.scope || UNIQUE_STREAM;
    const ip = rawClick.ipString || rawClick.ip;
    const userAgent = rawClick.userAgent;
    const campaignId = rawClick.campaignId || '';
    const streamId = rawClick.streamId || '';

    if (!ip) {
      return { passed: false, reason: 'IP not resolved for uniqueness check' };
    }

    let isUnique = false;

    switch (scope) {
      case UNIQUE_STREAM:
        isUnique = isUniqueForStream(ip, userAgent, streamId, campaignId);
        break;
      case UNIQUE_CAMPAIGN:
        isUnique = rawClick.isUniqueCampaign;
        break;
      case UNIQUE_GLOBAL:
        isUnique = rawClick.isUniqueGlobal;
        break;
      default:
        isUnique = isUniqueForStream(ip, userAgent, streamId, campaignId);
    }

    // In accept mode: pass if unique
    // In reject mode: pass if NOT unique (reject unique visitors)
    return {
      passed: isUnique,
      reason: isUnique
        ? `Visitor is unique for scope: ${scope}`
        : `Visitor is not unique for scope: ${scope}`,
      matchedValue: scope
    };
  }
}

/**
 * Cleanup old sessions (TTL-based)
 */
export function cleanupSessions(ttlHours: number = 24): void {
  const cutoff = new Date(Date.now() - ttlHours * 60 * 60 * 1000);
  
  for (const [key, session] of sessionStore.entries()) {
    if (session.lastSeen < cutoff) {
      sessionStore.delete(key);
    }
  }
}
