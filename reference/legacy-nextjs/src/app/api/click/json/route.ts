/**
 * Click API (JSON Context)
 *
 * Returns JSON responses instead of HTTP redirects — used by embedded
 * placements (iframe, script-tag, SPA) that need to receive the action
 * parameters and perform the redirect themselves.
 *
 * All processing is delegated to the full Pipeline engine (23 stages),
 * same as /api/click, but the final response is serialised as JSON
 * instead of an HTTP 302 redirect.
 *
 * Based on Keitaro's ClickApiContext / JSON click handler.
 */

import type { NextRequest } from 'next/server';
import { runPipeline, pipelinePayloadToJsonResponse } from '@/lib/tds/pipeline/runner';

export async function GET(request: NextRequest) {
  const startTime = Date.now();
  const result = await runPipeline(request);
  return pipelinePayloadToJsonResponse(result, startTime);
}

export async function POST(request: NextRequest) {
  return GET(request);
}
