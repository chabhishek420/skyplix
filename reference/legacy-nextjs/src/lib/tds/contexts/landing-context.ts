/**
 * Landing Page Context
 * Handles landing page serving and LP → Offer flow
 * Based on Keitaro's LandingContext.php
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { lpTokenService, type LpTokenPayload } from '@/lib/tds/services/lp-token-service';
import { macroRegistry } from '@/lib/tds/macros/registry';
import { processMacros } from '@/lib/tds/macros/processor';

interface LandingContextOptions {
  landingId: string;
  clickId: string;
  campaignId: string;
  streamId: string;
}

/**
 * Serve landing page with macro substitution
 */
export async function serveLandingPage(
  request: NextRequest,
  options: LandingContextOptions
): Promise<NextResponse> {
  try {
    // Get landing page
    const landing = await db.landing.findUnique({
      where: { id: options.landingId }
    });

    if (!landing) {
      return NextResponse.json(
        { error: 'Landing page not found' },
        { status: 404 }
      );
    }

    // Get click data for macro substitution
    const click = await db.click.findUnique({
      where: { clickId: options.clickId },
      include: {
        campaign: true,
        stream: true
      }
    });

    // Generate LP token for offer tracking
    const tokenPayload: LpTokenPayload = {
      clickId: options.clickId,
      campaignId: options.campaignId,
      streamId: options.streamId,
      landingId: options.landingId,
      timestamp: Date.now()
    };
    const lpToken = lpTokenService.generateToken(tokenPayload);

    // Build raw click data for macros
    const rawClick = click ? {
      clickId: click.clickId,
      campaignId: click.campaignId,
      streamId: click.streamId,
      landingId: click.landingId,
      offerId: click.offerId,
      ip: click.ip || '',
      ipString: click.ip || '',
      userAgent: click.userAgent || '',
      referrer: click.referrer,
      country: click.country,
      city: click.city,
      region: click.region,
      browser: click.browser,
      browserVersion: click.browserVersion,
      os: click.os,
      osVersion: click.osVersion,
      deviceType: click.deviceType,
      deviceModel: click.deviceModel,
      deviceBrand: click.deviceBrand,
      isMobile: click.isMobile,
      keyword: click.keyword,
      source: click.source,
      language: click.language,
      subId: click.sub1,
      subId1: click.sub1,
      subId2: click.sub2,
      subId3: click.sub3,
      subId4: click.sub4,
      subId5: click.sub5,
      isBot: click.isBot,
      botReason: click.botReason,
      botType: click.botType,
      isUsingProxy: click.isUsingProxy,
      isUniqueCampaign: click.isUniqueCampaign,
      isUniqueStream: click.isUniqueStream,
      isUniqueGlobal: click.isUniqueGlobal,
      isLead: click.isLead,
      isSale: click.isSale,
      isRejected: click.isRejected,
      leadRevenue: click.leadRevenue,
      saleRevenue: click.saleRevenue,
      rejectedRevenue: click.rejectedRevenue,
      cost: click.cost,
      token: lpToken,
      datetime: click.clickedAt
    } : null;

    // Get landing URL with macro substitution
    let landingUrl = landing.url || '';
    if (rawClick) {
      landingUrl = processMacros(landingUrl, rawClick, macroRegistry);
    }

    // Inject LP token into URL for tracking
    if (landingUrl) {
      landingUrl = lpTokenService.injectTokenIntoUrl(landingUrl, lpToken);
    }

    // Determine action type
    const actionType = landing.actionType || 'http_redirect';

    switch (actionType) {
      case 'iframe':
        return serveIframeLanding(landingUrl, landing.name);
      
      case 'content':
      case 'show_html':
        return serveHtmlContent(landing.actionPayload || '', rawClick);
      
      case 'http_redirect':
      case 'http302':
      default:
        // Update click with landing URL
        if (click) {
          await db.click.update({
            where: { clickId: options.clickId },
            data: {
              landingId: options.landingId,
              landingUrl: landingUrl
            }
          });
        }
        return NextResponse.redirect(landingUrl, 302);
    }
  } catch (error) {
    console.error('Error serving landing page:', error);
    return NextResponse.json(
      { error: 'Failed to serve landing page' },
      { status: 500 }
    );
  }
}

/**
 * Serve landing page in iframe
 */
function serveIframeLanding(url: string, title: string): NextResponse {
  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title || 'Loading...'}</title>
  <style>
    * { margin: 0; padding: 0; }
    html, body { width: 100%; height: 100%; overflow: hidden; }
    iframe { width: 100%; height: 100%; border: none; }
  </style>
</head>
<body>
  <iframe src="${url}" allowfullscreen></iframe>
</body>
</html>`;

  return new NextResponse(html, {
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Cache-Control': 'no-store, no-cache, must-revalidate'
    }
  });
}

/**
 * Serve HTML content with macro substitution
 */
function serveHtmlContent(html: string, rawClick: unknown): NextResponse {
  let processedHtml = html;
  
  if (rawClick) {
    processedHtml = processMacros(html, rawClick as Record<string, unknown>, macroRegistry);
  }

  return new NextResponse(processedHtml, {
    headers: {
      'Content-Type': 'text/html; charset=utf-8',
      'Cache-Control': 'no-store, no-cache, must-revalidate'
    }
  });
}

/**
 * Handle LP → Offer click-through
 * Called when visitor clicks through from landing page to offer
 */
export async function handleLpToOfferClick(
  request: NextRequest,
  token: string
): Promise<NextResponse> {
  try {
    // Validate LP token
    const tokenPayload = lpTokenService.validateToken(token);
    
    if (!tokenPayload) {
      return NextResponse.json(
        { error: 'INVALID_TOKEN', message: 'LP token is invalid or expired' },
        { status: 400 }
      );
    }

    // Get click
    const click = await db.click.findUnique({
      where: { clickId: tokenPayload.clickId },
      include: {
        campaign: true,
        stream: {
          include: {
            offerAssociations: {
              where: { status: 'active' },
              include: { offer: true }
            }
          }
        }
      }
    });

    if (!click) {
      return NextResponse.json(
        { error: 'CLICK_NOT_FOUND', message: 'Click not found' },
        { status: 404 }
      );
    }

    // Get offer from stream associations or token
    let offer = null;
    
    // First check if offer was pre-selected
    if (click.offerId) {
      offer = await db.offer.findUnique({
        where: { id: click.offerId }
      });
    }

    // If no offer, select from stream associations (weight-based)
    if (!offer && click.stream?.offerAssociations?.length) {
      const associations = click.stream.offerAssociations;
      const totalWeight = associations.reduce((sum: number, a: { share: number }) => sum + a.share, 0);
      let random = Math.random() * totalWeight;

      for (const assoc of associations) {
        random -= assoc.share;
        if (random <= 0 && assoc.offer) {
          offer = assoc.offer;
          break;
        }
      }
    }

    if (!offer) {
      return NextResponse.json(
        { error: 'NO_OFFER', message: 'No offer available' },
        { status: 404 }
      );
    }

    // Build raw click for macros
    const rawClick = {
      clickId: click.clickId,
      campaignId: click.campaignId,
      streamId: click.streamId,
      landingId: click.landingId,
      offerId: offer.id,
      ip: click.ip || '',
      ipString: click.ip || '',
      userAgent: click.userAgent || '',
      referrer: click.referrer,
      country: click.country,
      city: click.city,
      region: click.region,
      browser: click.browser,
      browserVersion: click.browserVersion,
      os: click.os,
      osVersion: click.osVersion,
      deviceType: click.deviceType,
      deviceModel: click.deviceModel,
      deviceBrand: click.deviceBrand,
      isMobile: click.isMobile,
      keyword: click.keyword,
      source: click.source,
      language: click.language,
      subId: click.sub1,
      subId1: click.sub1,
      subId2: click.sub2,
      subId3: click.sub3,
      subId4: click.sub4,
      subId5: click.sub5,
      datetime: click.clickedAt
    };

    // Process offer URL with macros
    let offerUrl = offer.url || '';
    offerUrl = processMacros(offerUrl, rawClick, macroRegistry);

    // Mark landing clicked
    await db.click.update({
      where: { clickId: tokenPayload.clickId },
      data: {
        landingClicked: true,
        landingClickedAt: new Date(),
        offerId: offer.id,
        destinationUrl: offerUrl
      }
    });

    // Redirect to offer
    return NextResponse.redirect(offerUrl, 302);
  } catch (error) {
    console.error('Error handling LP to offer click:', error);
    return NextResponse.json(
      { error: 'INTERNAL_ERROR', message: 'Failed to process click' },
      { status: 500 }
    );
  }
}
