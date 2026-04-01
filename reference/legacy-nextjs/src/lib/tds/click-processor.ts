/**
 * Click Processing Engine
 * 
 * Main traffic distribution logic:
 * 1. Validate request parameters
 * 2. Check campaign/publisher status
 * 3. Detect bots and apply cloaking
 * 4. Generate click ID and session
 * 5. Build destination URL
 * 6. Record click in database
 */

import { db } from '@/lib/db';
import { generateUniqueClickId, isValidClickId, parseClickId } from './click-id';
import { detectBot, shouldCloak, type DetectionContext, type BotDetectionResult } from './bot-detection';

export interface ClickRequest {
  campaignId: string | number;
  pubId: string | number;
  
  // Optional tracking parameters (Keitaro supports 15 sub IDs)
  source?: string;
  sub1?: string;
  sub2?: string;
  sub3?: string;
  sub4?: string;
  sub5?: string;
  sub6?: string;
  sub7?: string;
  sub8?: string;
  sub9?: string;
  sub10?: string;
  sub11?: string;
  sub12?: string;
  sub13?: string;
  sub14?: string;
  sub15?: string;
  
  // Request context
  ip?: string;
  userAgent?: string;
  referrer?: string;
  language?: string;
  country?: string;
  headers?: Record<string, string>;
  cookies?: Record<string, string>;
}

export interface ClickResult {
  success: boolean;
  error?: string;
  
  // Click data
  clickId?: string;
  destinationUrl?: string;
  
  // Cloaking
  showSafePage?: boolean;
  safePageUrl?: string;
  botReason?: string;
  
  // Session
  sessionId?: string;
  setCookie?: string;
}

/**
 * Process incoming click request
 */
export async function processClick(request: ClickRequest): Promise<ClickResult> {
  try {
    // 1. Validate required parameters
    if (!request.campaignId) {
      return { success: false, error: 'INVALID_CAMPAIGN_ID' };
    }
    
    if (!request.pubId) {
      return { success: false, error: 'INVALID_PUBLISHER_ID' };
    }
    
    const campaignIdNum = typeof request.campaignId === 'string' 
      ? parseInt(request.campaignId, 10) 
      : request.campaignId;
    
    const pubIdNum = typeof request.pubId === 'string'
      ? parseInt(request.pubId, 10)
      : request.pubId;
    
    if (isNaN(campaignIdNum)) {
      return { success: false, error: 'INVALID_CAMPAIGN_ID' };
    }
    
    if (isNaN(pubIdNum)) {
      return { success: false, error: 'INVALID_PUBLISHER_ID' };
    }
    
    // 2. Lookup campaign
    const campaign = await db.campaign.findUnique({
      where: { campaignId: campaignIdNum },
      include: { 
        streams: { 
          where: { status: 'active' },
          orderBy: { weight: 'desc' }
        },
        publisherAccess: {
          where: { 
            publisher: { pubId: pubIdNum },
            status: 'active'
          }
        }
      }
    });
    
    if (!campaign) {
      return { success: false, error: 'INVALID_OFFER_ID' };
    }
    
    if (campaign.status !== 'active') {
      return { success: false, error: 'ADV_INACTIVE' };
    }
    
    // 3. Lookup publisher
    const publisher = await db.publisher.findUnique({
      where: { pubId: pubIdNum }
    });
    
    if (!publisher) {
      return { success: false, error: 'INVALID_PUBLISHER_ID' };
    }
    
    if (publisher.status !== 'active') {
      return { success: false, error: 'PUBLISHER_NOT_ACTIVE' };
    }
    
    // 4. Check publisher access
    const hasAccess = campaign.publisherAccess.some(
      access => access.publisherId === publisher.id && access.status === 'active'
    );
    
    if (!hasAccess && campaign.publisherAccess.length > 0) {
      return { success: false, error: 'INSUFFICIENT_PERMISSION' };
    }
    
    // 5. Bot detection
    const detectionContext: DetectionContext = {
      userAgent: request.userAgent || null,
      ip: request.ip || null,
      referrer: request.referrer || null,
      headers: request.headers || {},
      params: { 
        campaignId: String(request.campaignId),
        pubId: String(request.pubId)
      },
      cookies: request.cookies || {}
    };
    
    const botDetection = await detectBot(detectionContext);

    // 6. Generate unique click ID with collision detection
    const clickId = await generateUniqueClickId();
    const sessionId = generateSessionId();
    
    // 7. Handle cloaking
    if (botDetection.isBot && botDetection.confidence >= 70) {
      // Record bot click
      await recordClick({
        clickId,
        campaignId: campaign.id,
        publisherId: publisher.id,
        ip: request.ip,
        userAgent: request.userAgent,
        referrer: request.referrer,
        source: request.source,
        sub1: request.sub1,
        sub2: request.sub2,
        sub3: request.sub3,
        sub4: request.sub4,
        sub5: request.sub5,
        sub6: request.sub6,
        sub7: request.sub7,
        sub8: request.sub8,
        sub9: request.sub9,
        sub10: request.sub10,
        sub11: request.sub11,
        sub12: request.sub12,
        sub13: request.sub13,
        sub14: request.sub14,
        sub15: request.sub15,
        isBot: true,
        botReason: botDetection.reason,
        showedSafePage: true,
        sessionId,
        destinationUrl: null
      });
      
      return {
        success: true,
        showSafePage: true,
        safePageUrl: campaign.safePageUrl || `/safe?lang=${request.language || 'en'}`,
        botReason: botDetection.reason || undefined,
        clickId,
        sessionId
      };
    }
    
    // 8. Select stream (weighted random)
    const stream = selectStream(campaign.streams);
    
    // 9. Build destination URL
    let destinationUrl: string;
    
    if (stream) {
      destinationUrl = buildDestinationUrl(stream.destinationUrl, {
        clickId,
        pubId: pubIdNum,
        offerId: campaign.offerId,
        affiliateId: campaign.affiliateId,
        source: request.source,
        sub1: request.sub1,
        sub2: request.sub2,
        sub3: request.sub3,
        sub4: request.sub4,
        sub5: request.sub5
      });
    } else if (campaign.destinationUrl) {
      destinationUrl = buildDestinationUrl(campaign.destinationUrl, {
        clickId,
        pubId: pubIdNum,
        offerId: campaign.offerId,
        affiliateId: campaign.affiliateId,
        source: request.source,
        sub1: request.sub1,
        sub2: request.sub2,
        sub3: request.sub3,
        sub4: request.sub4,
        sub5: request.sub5
      });
    } else {
      return { success: false, error: 'INVALID_OFFER_ID' };
    }
    
    // 10. Record click
    await recordClick({
      clickId,
      campaignId: campaign.id,
      streamId: stream?.id || null,
      publisherId: publisher.id,
      ip: request.ip,
      userAgent: request.userAgent,
      referrer: request.referrer,
      source: request.source,
      sub1: request.sub1,
      sub2: request.sub2,
      sub3: request.sub3,
      sub4: request.sub4,
      sub5: request.sub5,
      sub6: request.sub6,
      sub7: request.sub7,
      sub8: request.sub8,
      sub9: request.sub9,
      sub10: request.sub10,
      sub11: request.sub11,
      sub12: request.sub12,
      sub13: request.sub13,
      sub14: request.sub14,
      sub15: request.sub15,
      isBot: false,
      botReason: null,
      showedSafePage: false,
      sessionId,
      destinationUrl
    });
    
    // 11. Update publisher stats
    await db.publisher.update({
      where: { id: publisher.id },
      data: { totalClicks: { increment: 1 } }
    });
    
    // 12. Return success with destination
    return {
      success: true,
      clickId,
      destinationUrl,
      sessionId,
      setCookie: buildSessionCookie(sessionId)
    };
    
  } catch (error) {
    console.error('Click processing error:', error);
    return { success: false, error: 'INTERNAL_ERROR' };
  }
}

/**
 * Select stream based on weight
 */
function selectStream(streams: { id: string; weight: number }[]): { id: string; weight: number } | null {
  if (streams.length === 0) return null;
  if (streams.length === 1) return streams[0];
  
  const totalWeight = streams.reduce((sum, s) => sum + s.weight, 0);
  let random = Math.random() * totalWeight;
  
  for (const stream of streams) {
    random -= stream.weight;
    if (random <= 0) return stream;
  }
  
  return streams[0];
}

/**
 * Build destination URL with tracking parameters
 */
function buildDestinationUrl(
  baseUrl: string, 
  params: {
    clickId: string;
    pubId: number;
    offerId?: number | null;
    affiliateId?: string | null;
    source?: string;
    sub1?: string;
    sub2?: string;
    sub3?: string;
    sub4?: string;
    sub5?: string;
  }
): string {
  const url = new URL(baseUrl);
  
  // Add standard affiliate parameters
  if (params.offerId) {
    url.searchParams.set('offer_id', String(params.offerId));
  }
  
  if (params.affiliateId) {
    url.searchParams.set('aff_id', params.affiliateId);
  }
  
  // Add click ID as aff_sub2 (like Keitaro)
  url.searchParams.set('aff_sub2', params.clickId);
  
  // Add pub_id as aff_sub
  url.searchParams.set('aff_sub', String(params.pubId));
  
  // Add source if provided
  if (params.source) {
    url.searchParams.set('source', params.source);
  }
  
  // Add sub parameters
  if (params.sub1) url.searchParams.set('sub1', params.sub1);
  if (params.sub2) url.searchParams.set('sub2', params.sub2);
  if (params.sub3) url.searchParams.set('sub3', params.sub3);
  if (params.sub4) url.searchParams.set('sub4', params.sub4);
  if (params.sub5) url.searchParams.set('sub5', params.sub5);
  
  return url.toString();
}

/**
 * Generate session ID
 */
function generateSessionId(): string {
  const timestamp = Date.now().toString(16);
  const random = Math.random().toString(16).substring(2, 14);
  return `sess_${timestamp}${random}`;
}

/**
 * Build session cookie string
 */
function buildSessionCookie(sessionId: string): string {
  const expires = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000); // 7 days
  return `${sessionId}; expires=${expires.toUTCString()}; path=/; HttpOnly`;
}

/**
 * Record click in database
 */
async function recordClick(data: {
  clickId: string;
  campaignId: string;
  streamId?: string | null;
  publisherId: string;
  ip?: string;
  userAgent?: string;
  referrer?: string;
  source?: string;
  sub1?: string;
  sub2?: string;
  sub3?: string;
  sub4?: string;
  sub5?: string;
  sub6?: string;
  sub7?: string;
  sub8?: string;
  sub9?: string;
  sub10?: string;
  sub11?: string;
  sub12?: string;
  sub13?: string;
  sub14?: string;
  sub15?: string;
  isBot: boolean;
  botReason?: string | null;
  showedSafePage: boolean;
  sessionId?: string;
  destinationUrl: string | null;
}): Promise<void> {
  await db.click.create({
    data: {
      clickId: data.clickId,
      campaignId: data.campaignId,
      streamId: data.streamId || null,
      publisherId: data.publisherId,
      ip: data.ip || null,
      userAgent: data.userAgent || null,
      referrer: data.referrer || null,
      source: data.source || null,
      sub1: data.sub1 || null,
      sub2: data.sub2 || null,
      sub3: data.sub3 || null,
      sub4: data.sub4 || null,
      sub5: data.sub5 || null,
      sub6: data.sub6 || null,
      sub7: data.sub7 || null,
      sub8: data.sub8 || null,
      sub9: data.sub9 || null,
      sub10: data.sub10 || null,
      sub11: data.sub11 || null,
      sub12: data.sub12 || null,
      sub13: data.sub13 || null,
      sub14: data.sub14 || null,
      sub15: data.sub15 || null,
      isBot: data.isBot,
      botReason: data.botReason || null,
      showedSafePage: data.showedSafePage,
      sessionId: data.sessionId || null,
      destinationUrl: data.destinationUrl,
      cookieSet: !!data.sessionId
    }
  });
}
