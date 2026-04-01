/**
 * Pipeline Runner
 * Thin adapter between Next.js route handlers and the Pipeline engine.
 *
 * Usage:
 *   const result = await runPipeline(request);          // first-level (click)
 *   const result = await runSecondLevelPipeline(request); // second-level (LP→offer)
 *   const response = pipelinePayloadToResponse(result.payload, request);
 */

import { NextRequest, NextResponse } from 'next/server';
import { Pipeline } from './pipeline';
import { Payload } from './payload';
import type { StageResult } from './types';

// ---------------------------------------------------------------------------
// Run first-level pipeline (full click flow)
// ---------------------------------------------------------------------------
export async function runPipeline(request: NextRequest): Promise<StageResult> {
  const payload = Payload.fromRequest(request);
  const pipeline = Pipeline.createDefault();
  return pipeline.runFirstLevel(payload);
}

// ---------------------------------------------------------------------------
// Run second-level pipeline (LP → Offer flow)
// ---------------------------------------------------------------------------
export async function runSecondLevelPipeline(request: NextRequest): Promise<StageResult> {
  const payload = Payload.fromRequest(request);
  payload.setPipelineLevel(2);
  const pipeline = Pipeline.createDefault();
  return pipeline.runSecondLevel(payload);
}

// ---------------------------------------------------------------------------
// Convert a completed pipeline payload into a NextResponse
// ---------------------------------------------------------------------------
export function pipelinePayloadToResponse(
  result: StageResult,
  request: NextRequest
): NextResponse {
  const payload = result.payload as Payload;

  // Build base headers from payload
  const responseHeaders: Record<string, string> = {
    'X-RT': result.success ? '1' : '0',
    ...payload.headers,
  };

  // Redirect response
  if (payload.redirectUrl) {
    const response = NextResponse.redirect(payload.redirectUrl, payload.statusCode || 302);
    for (const [key, value] of Object.entries(responseHeaders)) {
      response.headers.set(key, value);
    }
    response.headers.set('Referrer-Policy', 'no-referrer');
    return response;
  }

  // Body response (HTML / text / JS)
  if (payload.body !== null) {
    const response = new NextResponse(payload.body, {
      status: payload.statusCode || 200,
      headers: {
        'Content-Type': payload.contentType || 'text/html; charset=utf-8',
        ...responseHeaders,
      },
    });

    // Append cookies
    if (payload.cookies && payload.cookies.length > 0) {
      for (const cookie of payload.cookies) {
        response.headers.append('Set-Cookie', cookie);
      }
    }

    return response;
  }


  // Fallback error (pipeline ran but produced nothing actionable)
  return new NextResponse(result.error || 'PIPELINE_NO_OUTPUT', {
    status: 200,
    headers: {
      'Content-Type': 'text/plain',
      'X-RT': '0',
    },
  });
}

// ---------------------------------------------------------------------------
// Convert a completed pipeline payload into a JSON NextResponse
// (used by /api/click/json)
// ---------------------------------------------------------------------------
export function pipelinePayloadToJsonResponse(
  result: StageResult,
  startTime: number
): NextResponse {
  const payload = result.payload as Payload;
  const click = payload.rawClick;

  if (!result.success || !click) {
    return NextResponse.json(
      {
        success: false,
        error: {
          code: result.error?.includes('not found') ? 'INVALID_OFFER_ID' : 'PIPELINE_ERROR',
          message: result.error || 'Pipeline failed',
        },
      },
      { status: 200 }
    );
  }

  return NextResponse.json({
    success: true,
    clickId: click.clickId,
    campaignId: payload.campaign?.campaignId,
    streamId: payload.stream?.id,
    streamName: payload.stream?.name,
    isBot: click.isBot,
    botReason: click.botReason || null,
    action: {
      type: payload.actionType,
      url: payload.redirectUrl || payload.actionPayload,
      statusCode: payload.statusCode,
    },
    processingTime: Date.now() - startTime,
  });
}
