/**
 * Gateway Redirect Context
 * Handles gateway-style redirects with additional processing
 * Based on Keitaro's GatewayRedirectContext.php
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { macroRegistry } from '@/lib/tds/macros/registry';
import { processMacros } from '@/lib/tds/macros/processor';

interface GatewayRedirectOptions {
  campaignId?: string;
  token?: string;
  forcedOfferId?: string;
  forcedLandingId?: string;
}

/**
 * Handle gateway redirect request
 * Gateway redirects allow for additional processing before final destination
 */
export async function handleGatewayRedirect(
  request: NextRequest,
  options: GatewayRedirectOptions
): Promise<NextResponse> {
  try {
    const { searchParams } = new URL(request.url);
    
    // Get campaign from options or URL
    const campaignId = options.campaignId || searchParams.get('campaign_id') || searchParams.get('cid');
    const token = options.token || searchParams.get('token');
    
    if (!campaignId && !token) {
      return NextResponse.json(
        { error: 'MISSING_PARAMS', message: 'Campaign ID or token required' },
        { status: 400 }
      );
    }

    // If token provided, decode it
    let clickId: string | null = null;
    let campaign: {
      id: string;
      destinationUrl: string | null;
      redirectType: string | null;
      streams: Array<{
        id: string;
        schema: string;
        weight: number;
        actionType: string | null;
        actionPayload: string | null;
        offerAssociations: Array<{ share: number; offer: { url: string | null } }>;
        landingAssociations: Array<{ share: number; landing: { url: string | null } }>;
      }>;
    } | null = null;

    if (token) {
      // Token could be a click_id or encoded payload
      clickId = token;
      const click = await db.click.findUnique({
        where: { clickId: token },
        include: { campaign: true }
      });
      if (click) {
        campaign = click.campaign as typeof campaign;
      }
    }

    // Get campaign if not from token
    if (!campaign && campaignId) {
      campaign = await db.campaign.findFirst({
        where: {
          OR: [
            { id: campaignId },
            { campaignId: parseInt(campaignId) || 0 },
            { token: campaignId }
          ]
        },
        include: {
          streams: {
            where: { status: 'active' },
            orderBy: [{ weight: 'desc' }],
            include: {
              offerAssociations: {
                where: { status: 'active' },
                include: { offer: true }
              },
              landingAssociations: {
                where: { status: 'active' },
                include: { landing: true }
              }
            }
          }
        }
      }) as typeof campaign;
    }

    if (!campaign) {
      return NextResponse.json(
        { error: 'CAMPAIGN_NOT_FOUND', message: 'Campaign not found' },
        { status: 404 }
      );
    }

    // Get click data if available
    let click = null;
    if (clickId) {
      click = await db.click.findUnique({
        where: { clickId }
      });
    }

    // Build raw click for macros
    const rawClick = click ? {
      clickId: click.clickId,
      campaignId: click.campaignId,
      streamId: click.streamId,
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
      datetime: click.clickedAt
    } : null;

    // Select stream (weight-based)
    let selectedStream = null;
    if (campaign.streams?.length) {
      const totalWeight = campaign.streams.reduce((sum: number, s: { weight: number }) => sum + s.weight, 0);
      let random = Math.random() * totalWeight;
      
      for (const stream of campaign.streams) {
        random -= stream.weight;
        if (random <= 0) {
          selectedStream = stream;
          break;
        }
      }
      
      if (!selectedStream) {
        selectedStream = campaign.streams[0];
      }
    }

    // Get destination URL
    let destinationUrl = campaign.destinationUrl;

    if (selectedStream) {
      // Check stream schema
      if (selectedStream.schema === 'url' && selectedStream.actionPayload) {
        destinationUrl = selectedStream.actionPayload;
      } else if (selectedStream.schema === 'offers' && selectedStream.offerAssociations?.length) {
        // Select offer by weight
        const offerAssocs = selectedStream.offerAssociations;
        const totalWeight = offerAssocs.reduce((sum: number, a: { share: number }) => sum + a.share, 0);
        let random = Math.random() * totalWeight;
        let selectedOffer: { url: string | null } | null = null;
        
        for (const assoc of offerAssocs) {
          random -= assoc.share;
          if (random <= 0) {
            selectedOffer = assoc.offer;
            break;
          }
        }
        
        if (selectedOffer) {
          destinationUrl = selectedOffer.url;
        }
      } else if (selectedStream.schema === 'landings' && selectedStream.landingAssociations?.length) {
        // Select landing by weight
        const landingAssocs = selectedStream.landingAssociations;
        const totalWeight = landingAssocs.reduce((sum: number, a: { share: number }) => sum + a.share, 0);
        let random = Math.random() * totalWeight;
        let selectedLanding: { url: string | null } | null = null;
        
        for (const assoc of landingAssocs) {
          random -= assoc.share;
          if (random <= 0) {
            selectedLanding = assoc.landing;
            break;
          }
        }
        
        if (selectedLanding) {
          destinationUrl = selectedLanding.url;
        }
      }
    }

    if (!destinationUrl) {
      return NextResponse.json(
        { error: 'NO_DESTINATION', message: 'No destination URL configured' },
        { status: 404 }
      );
    }

    // Process macros in URL
    if (rawClick) {
      destinationUrl = processMacros(destinationUrl, rawClick, macroRegistry);
    }

    // Get redirect type from campaign or stream
    const redirectType = selectedStream?.actionType || campaign.redirectType || 'http302';

    // Execute redirect based on type
    switch (redirectType) {
      case 'meta':
        return serveMetaRedirect(destinationUrl);
      
      case 'js':
        return serveJsRedirect(destinationUrl);
      
      case 'iframe':
        return serveIframeRedirect(destinationUrl);
      
      case 'http301':
        return NextResponse.redirect(destinationUrl, 301);
      
      case 'http302':
      case 'http_redirect':
      default:
        return NextResponse.redirect(destinationUrl, 302);
    }
  } catch (error) {
    console.error('Error handling gateway redirect:', error);
    return NextResponse.json(
      { error: 'INTERNAL_ERROR', message: 'Failed to process gateway redirect' },
      { status: 500 }
    );
  }
}

/**
 * Serve meta refresh redirect
 */
function serveMetaRedirect(url: string): NextResponse {
  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="0;url=${url}">
  <title>Redirecting...</title>
</head>
<body>
  <p>Redirecting...</p>
  <script>window.location.href = "${url}";</script>
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
 * Serve JavaScript redirect
 */
function serveJsRedirect(url: string): NextResponse {
  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Redirecting...</title>
</head>
<body>
  <script>
    window.location.href = "${url}";
  </script>
  <noscript>
    <meta http-equiv="refresh" content="0;url=${url}">
  </noscript>
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
 * Serve iframe redirect
 */
function serveIframeRedirect(url: string): NextResponse {
  const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    * { margin: 0; padding: 0; }
    html, body { width: 100%; height: 100%; overflow: hidden; }
    iframe { width: 100%; height: 100%; border: none; }
  </style>
</head>
<body>
  <iframe src="${url}"></iframe>
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
 * Generate gateway URL for a campaign
 */
export function generateGatewayUrl(
  baseUrl: string,
  campaignId: string,
  options?: {
    token?: string;
    clickId?: string;
    sub1?: string;
    sub2?: string;
  }
): string {
  const url = new URL('/gateway', baseUrl);
  
  url.searchParams.set('cid', campaignId);
  
  if (options?.token) {
    url.searchParams.set('token', options.token);
  }
  if (options?.clickId) {
    url.searchParams.set('click_id', options.clickId);
  }
  if (options?.sub1) {
    url.searchParams.set('sub1', options.sub1);
  }
  if (options?.sub2) {
    url.searchParams.set('sub2', options.sub2);
  }
  
  return url.toString();
}
