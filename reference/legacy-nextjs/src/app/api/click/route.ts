/**
 * Click API Endpoint
 *
 * Primary traffic entry point for the TDS.
 * Handles /click and /click.php requests.
 *
 * All processing is delegated to the full Pipeline engine (23 stages):
 *   BuildRawClick → CheckBot → FindCampaign → ChooseStream →
 *   ChooseLanding → ChooseOffer → ExecuteAction → StoreRawClicks
 *
 * Parameters:
 * - campaign_id: Campaign identifier (required)
 * - pub_id: Publisher identifier (required)
 * - source, sub1-sub15, keyword: Optional tracking parameters
 */

import type { NextRequest } from 'next/server';
import { runPipeline, pipelinePayloadToResponse } from '@/lib/tds/pipeline/runner';

export async function GET(request: NextRequest) {
  const result = await runPipeline(request);
  return pipelinePayloadToResponse(result, request);
}

// POST is identical — the pipeline reads from query params and body
export async function POST(request: NextRequest) {
  return GET(request);
}
