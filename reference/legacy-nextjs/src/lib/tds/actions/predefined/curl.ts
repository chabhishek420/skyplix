/**
 * CurlAction
 * 
 * Executes cURL request and displays result.
 * Based on Keitaro's Curl.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class CurlAction extends BaseAction {
  name = 'curl';
  weight = 3;

  async execute(): Promise<ActionResult> {
    const rawUrl = this.payload.actionPayload;

    if (!rawUrl) {
      this.setBody('Error: No URL provided', 'text/plain');
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided'
      };
    }

    try {
      // Process macros in URL
      const url = this.processMacros(rawUrl);
      this.addLog(`Curl action: Fetching ${url}`);

      // Execute cURL-like request
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'User-Agent': this.payload.rawClick?.userAgent || 'TDS Bot',
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          'Accept-Language': this.payload.rawClick?.language || 'en-US,en;q=0.5'
        },
        redirect: 'follow'
      });

      // Get response body
      const contentType = response.headers.get('content-type') || 'text/html';
      const body = await response.text();

      this.setBody(body, contentType);
      this.setDestinationInfo(url);

      // Copy relevant headers from response
      const cacheControl = response.headers.get('cache-control');
      if (cacheControl) {
        this.addHeader('Cache-Control', cacheControl);
      }

      return {
        success: true,
        payload: this.payload
      };
    } catch (error) {
      const errorMsg = error instanceof Error ? error.message : String(error);
      this.addLog(`Curl action error: ${errorMsg}`);
      this.setBody(`Fetch error: ${errorMsg}`, 'text/plain');

      return {
        success: false,
        payload: this.payload,
        error: errorMsg
      };
    }
  }
}
