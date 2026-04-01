/**
 * RemoteAction
 * 
 * Fetches URL from remote server and uses it for redirect.
 * Based on Keitaro's Remote.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class RemoteAction extends BaseAction {
  name = 'remote';
  weight = 4;

  async execute(): Promise<ActionResult> {
    const rawUrl = this.payload.actionPayload;

    if (!rawUrl) {
      this.addLog('Remote action: No URL provided');
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
      this.addLog(`Remote action: Fetching from ${url}`);

      // Fetch the remote URL
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'User-Agent': this.payload.rawClick?.userAgent || 'TDS Bot',
          'Accept': '*/*'
        },
        redirect: 'follow'
      });

      if (!response.ok) {
        throw new Error(`Remote fetch failed: ${response.status}`);
      }

      // Get the final URL after redirects
      const finalUrl = response.url || url;
      this.addLog(`Remote action: Final URL is ${finalUrl}`);

      // Set destination and redirect
      this.setDestinationInfo(finalUrl);
      this.setRedirect(finalUrl, 302);

      return {
        success: true,
        payload: this.payload
      };
    } catch (error) {
      const errorMsg = error instanceof Error ? error.message : String(error);
      this.addLog(`Remote action error: ${errorMsg}`);
      this.setBody(`Remote fetch error: ${errorMsg}`, 'text/plain');
      this.setStatus(500);

      return {
        success: false,
        payload: this.payload,
        error: errorMsg
      };
    }
  }
}
