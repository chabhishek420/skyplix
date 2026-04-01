/**
 * LP Offer API
 *
 * Landing Page → Offer flow.
 * Called when the visitor clicks a CTA on a landing page.
 *
 * GET  /api/lp/offer?lp_token=<token>  — token-based offer resolution
 * POST /api/lp/offer                   — direct click_id based tracking
 *
 * GET path: delegates to the second-level Pipeline (13 stages):
 *   FindCampaign → UpdateParamsFromLanding → ChooseStream → ChooseOffer →
 *   ExecuteAction → StoreRawClicks
 *
 * POST path: lightweight direct tracking (no full pipeline needed — the
 *   click already exists, we just mark landing_clicked and return the
 *   offer URL for the client to redirect to).
 *
 * Based on Keitaro's LpOfferController / second-level pipeline.
 */

import { NextRequest, NextResponse } from 'next/server';
import { runSecondLevelPipeline, pipelinePayloadToResponse } from '@/lib/tds/pipeline/runner';
import { db } from '@/lib/db';

// ---------------------------------------------------------------------------
// GET — token-based LP→Offer via second-level pipeline
// ---------------------------------------------------------------------------
export async function GET(request: NextRequest) {
  const token = request.nextUrl.searchParams.get('lp_token')
    || request.nextUrl.searchParams.get('token');

  if (!token) {
    return NextResponse.json(
      { success: false, error: 'LP token is required' },
      { status: 400 }
    );
  }

  // Delegate all routing logic to the second-level pipeline.
  // The pipeline's UpdateParamsFromLandingStage reads the token,
  // resolves the click, marks landing_clicked, and picks the offer.
  const result = await runSecondLevelPipeline(request);
  return pipelinePayloadToResponse(result, request);
}

// ---------------------------------------------------------------------------
// POST — direct click_id based tracking (lightweight, no full pipeline)
// ---------------------------------------------------------------------------
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    const clickId = body.clickId || body.click_id;
    const offerId = body.offerId || body.offer_id;

    if (!clickId) {
      return NextResponse.json(
        { success: false, error: 'Click ID is required' },
        { status: 400 }
      );
    }

    // Find the click
    const click = await db.click.findUnique({ where: { clickId } });

    if (!click) {
      return NextResponse.json(
        { success: false, error: 'Click not found' },
        { status: 404 }
      );
    }

    // Mark landing clicked
    await db.click.update({
      where: { id: click.id },
      data: {
        landingClicked: true,
        landingClickedAt: new Date(),
        offerId: offerId || click.offerId,
      },
    });

    // Resolve offer URL
    let offerUrl: string | null = click.destinationUrl;

    if (offerId) {
      const offer = await db.offer.findUnique({ where: { id: offerId } });
      if (offer?.url) offerUrl = offer.url;
    }

    return NextResponse.json({
      success: true,
      clickId: click.clickId,
      offerUrl,
      landingClicked: true,
    });

  } catch (error) {
    console.error('LP offer POST error:', error);
    return NextResponse.json(
      { success: false, error: 'Internal error' },
      { status: 500 }
    );
  }
}
