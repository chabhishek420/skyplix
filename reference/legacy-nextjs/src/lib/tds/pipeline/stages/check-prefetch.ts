/**
 * CheckPrefetchStage
 * 
 * Detects prefetch requests (Google Instant, etc.)
 * These should be handled differently as they're not real clicks.
 * Based on Keitaro's CheckPrefetchStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';


export class CheckPrefetchStage implements StageInterface {
  name = 'CheckPrefetchStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.request) {
      return {
        success: false,
        payload,
        error: 'Empty request',
        abort: true
      };
    }

    const request = payload.request;
    
    // Check for prefetch headers
    const purpose = request.headers.get('purpose');
    const secPurpose = request.headers.get('sec-purpose');
    const secFetchDest = request.headers.get('sec-fetch-dest');
    const xMoz = request.headers.get('x-moz');

    // Google Instant Preview
    if (purpose === 'prefetch' || secPurpose === 'prefetch') {
      payload.log('Prefetch request detected (purpose header)');
      payload.abort();
      payload.setBody('', 'text/html');
      payload.statusCode = 204; // No Content
      return {
        success: true,
        payload,
        abort: true
      };
    }

    // Firefox prefetch
    if (xMoz === 'prefetch') {
      payload.log('Firefox prefetch request detected');
      payload.abort();
      payload.setBody('', 'text/html');
      payload.statusCode = 204;
      return {
        success: true,
        payload,
        abort: true
      };
    }

    // Sec-Fetch-Dest: empty (prefetch/prerender)
    if (secFetchDest === 'empty') {
      payload.log('Sec-Fetch-Dest: empty detected (possible prefetch)');
      // Don't abort but mark as prefetch
      if (payload.rawClick) {
        payload.rawClick.xRequestedWith = 'prefetch';
      }
    }

    return {
      success: true,
      payload
    };
  }
}
