/**
 * Meta Refresh Redirect Action
 * Uses HTML meta refresh tag for redirect
 * Can be used to partially hide referrer
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

export class MetaRedirectAction extends AbstractAction {
  /**
   * Execute meta refresh redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for meta redirect'
      };
    }

    const delay = this.options.delay || 0;
    
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="${delay};url=${url}">
  <title>Redirecting...</title>
</head>
<body>
  <p>Redirecting...</p>
  <script>window.location.href="${url}";</script>
</body>
</html>`;

    this.setBody(html);
    this.setStatus(200);
    this.setDestinationInfo(url);
    this.addLog(`Meta refresh redirect to: ${url} (delay: ${delay}s)`);

    return {
      success: true,
      payload: this.payload
    };
  }
}

/**
 * Double Meta Refresh Action
 * Uses two consecutive meta refreshes to blank the referrer
 * More effective at hiding referrer than single meta refresh
 */
export class DoubleMetaRedirectAction extends AbstractAction {
  /**
   * Execute double meta refresh redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for double meta redirect'
      };
    }

    // First meta refresh goes to intermediate page
    // Second meta refresh goes to final destination
    const intermediateUrl = this.payload.request?.url || '';
    
    // Generate intermediate page that will do the second redirect
    const intermediateHtml = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0;url=${url}">
  <title>Redirecting...</title>
</head>
<body>
  <script>
    // Double meta refresh to blank referrer
    var meta = document.createElement('meta');
    meta.httpEquiv = 'refresh';
    meta.content = '0;url=${url}';
    document.head.appendChild(meta);
    setTimeout(function() { window.location.href = '${url}'; }, 10);
  </script>
</body>
</html>`;

    this.setBody(intermediateHtml);
    this.setStatus(200);
    this.setDestinationInfo(url);
    this.addLog(`Double meta refresh redirect to: ${url}`);

    return {
      success: true,
      payload: this.payload
    };
  }
}
