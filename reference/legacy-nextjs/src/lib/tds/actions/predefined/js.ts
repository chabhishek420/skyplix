/**
 * JavaScript Redirect Action
 * Uses JavaScript to redirect (can partially hide referrer)
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

export class JsRedirectAction extends AbstractAction {
  /**
   * Execute JavaScript redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for JS redirect'
      };
    }

    const jsDelay = this.options.jsDelay || 0;

    const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Redirecting...</title>
</head>
<body>
  <script>
    setTimeout(function() {
      window.location.href = "${url}";
    }, ${jsDelay});
  </script>
  <noscript>
    <meta http-equiv="refresh" content="0;url=${url}">
    <a href="${url}">Click here to continue</a>
  </noscript>
</body>
</html>`;

    this.setBody(html);
    this.setStatus(200);
    this.setDestinationInfo(url);
    this.addLog(`JavaScript redirect to: ${url} (delay: ${jsDelay}ms)`);

    return {
      success: true,
      payload: this.payload
    };
  }
}
