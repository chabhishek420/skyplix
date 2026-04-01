/**
 * DoubleMetaAction
 * 
 * Double meta refresh redirect - hides referrer by using two consecutive meta refreshes.
 * Based on Keitaro's DoubleMeta.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';
import type { StageResult } from '../../pipeline/types';

export class DoubleMetaAction extends BaseAction {
  name = 'double_meta';
  weight = 3;

  async execute(): Promise<ActionResult> {
    const url = this.getProcessedPayload();

    // Check context (frame, script, default)
    const context = this.getExecutionContext();

    if (context === 'script') {
      // JavaScript redirect for script context
      this.setBody(this.scriptRedirect(url), 'application/javascript');
    } else if (context === 'frame') {
      // Frame redirect for iframe context
      this.setBody(this.frameRedirect(url), 'text/html');
    } else {
      // Default: meta refresh to gateway
      const gatewayUrl = this.getGatewayUrl(url);
      this.setBody(this.metaRedirect(gatewayUrl), 'text/html');
    }

    this.setDestinationInfo(url);

    return {
      success: true,
      payload: this.payload
    };
  }

  /**
   * Get gateway URL for double meta redirect
   */
  private getGatewayUrl(targetUrl: string): string {
    const request = this.payload.request;
    if (!request) return targetUrl;

    const url = new URL(request.url);
    const gatewayUrl = `${url.protocol}//${url.host}/gateway?token=${this.encodeToken(targetUrl)}`;

    return gatewayUrl;
  }

  /**
   * Encode token with target URL
   */
  private encodeToken(targetUrl: string): string {
    const token = JSON.stringify({ url: targetUrl, ts: Date.now() });
    return Buffer.from(token).toString('base64url');
  }

  /**
   * Meta refresh HTML
   */
  private metaRedirect(url: string): string {
    return `<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="0;url=${this.escapeHtml(url)}">
  <title>Redirecting...</title>
</head>
<body>
  <p>Redirecting...</p>
  <script>window.location.href="${this.escapeJs(url)}";</script>
</body>
</html>`;
  }

  /**
   * Frame redirect HTML
   */
  private frameRedirect(url: string): string {
    return `<!DOCTYPE html>
<html>
<head>
  <title></title>
</head>
<frameset rows="100%">
  <frame src="${this.escapeHtml(url)}" frameborder="0">
</frameset>
</html>`;
  }

  /**
   * Script redirect JS
   */
  private scriptRedirect(url: string): string {
    return `window.location.href="${this.escapeJs(url)}";`;
  }

  private escapeHtml(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  private escapeJs(str: string): string {
    return str.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n');
  }
}
