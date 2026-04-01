/**
 * JsForIframe and JsForScript Actions
 * Context-aware JS redirect variants
 *
 * Based on Keitaro's JsForIframe.php and JsForScript.php
 *
 * JsForIframe — used when the campaign runs inside an iframe embed.
 *   Context detection follows the PHP `_executeInContext()` pattern:
 *   - frm=frame  → frame-style redirect (breaks out of parent)
 *   - default    → same frame redirect
 *
 * JsForScript — used when the campaign is loaded via a <script> tag.
 *   - frm=script → returns application/javascript that redirects
 *   - frm=frame  → returns text/html frame redirect
 *   - default    → same as script mode
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

// ---------------------------------------------------------------------------
// JsForIframe
// ---------------------------------------------------------------------------

export class JsForIframeAction extends AbstractAction {
  /** Weight mirrors PHP $_weight = 999 (lower priority than standard JS) */
  readonly weight = 999;

  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();

    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for JsForIframe redirect',
      };
    }

    const processedUrl = this.processMacros(url);
    const ctx = this.getExecutionContext();

    // Both 'frame' and 'default' produce the same frame-breakout redirect
    const html = this.buildFrameRedirect(processedUrl);
    this.setBody(html, 'text/html; charset=utf-8');
    this.setStatus(200);
    this.setDestinationInfo(processedUrl);
    this.addLog(`JsForIframe redirect [ctx=${ctx}] to: ${processedUrl}`);

    return { success: true, payload: this.payload };
  }

  /**
   * Generates a JS snippet that breaks out of an iframe and redirects the
   * top-level window — mirrors RedirectService::frameRedirect() in PHP.
   */
  private buildFrameRedirect(url: string): string {
    return `<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Redirecting...</title></head>
<body>
<script>
  (function() {
    var url = ${JSON.stringify(url)};
    try {
      if (window.top !== window.self) {
        window.top.location.href = url;
      } else {
        window.location.href = url;
      }
    } catch (e) {
      window.location.href = url;
    }
  })();
</script>
<noscript><meta http-equiv="refresh" content="0;url=${url}"><a href="${url}">Continue</a></noscript>
</body>
</html>`;
  }
}

// ---------------------------------------------------------------------------
// JsForScript
// ---------------------------------------------------------------------------

export class JsForScriptAction extends AbstractAction {
  /** Weight mirrors PHP $_weight = 900 */
  readonly weight = 900;

  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();

    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for JsForScript redirect',
      };
    }

    const processedUrl = this.processMacros(url);
    const ctx = this.getExecutionContext();

    if (ctx === 'frame') {
      // Serve an HTML frame-style redirect
      const html = this.buildFrameRedirect(processedUrl);
      this.setBody(html, 'text/html; charset=utf-8');
    } else {
      // Default & script: serve bare JS that executes a redirect
      const js = this.buildScriptRedirect(processedUrl);
      this.setBody(js, 'application/javascript');
    }

    this.setStatus(200);
    this.setDestinationInfo(processedUrl);
    this.addLog(`JsForScript redirect [ctx=${ctx}] to: ${processedUrl}`);

    return { success: true, payload: this.payload };
  }

  /**
   * Returns a bare JS snippet — mirrors RedirectService::scriptRedirect().
   * Designed to be loaded via <script src="..."> on an external page.
   */
  private buildScriptRedirect(url: string): string {
    return `(function(){var u=${JSON.stringify(url)};if(typeof window!=='undefined'){window.location.href=u;}})();`;
  }

  /** Same frame-breakout HTML as JsForIframe */
  private buildFrameRedirect(url: string): string {
    return `<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Redirecting...</title></head>
<body>
<script>
  (function() {
    var url = ${JSON.stringify(url)};
    try {
      if (window.top !== window.self) { window.top.location.href = url; }
      else { window.location.href = url; }
    } catch(e) { window.location.href = url; }
  })();
</script>
<noscript><meta http-equiv="refresh" content="0;url=${url}"><a href="${url}">Continue</a></noscript>
</body>
</html>`;
  }
}
