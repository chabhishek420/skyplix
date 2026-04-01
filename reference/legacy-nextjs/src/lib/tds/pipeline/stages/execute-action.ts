/**
 * ExecuteActionStage
 * 
 * Executes the selected action (redirect, show content, etc.)
 * Based on Keitaro's ExecuteActionStage.php and Actions
 * 
 * Supported actions:
 * - http_redirect: HTTP 302 redirect
 * - http301: HTTP 301 redirect
 * - meta: Meta refresh redirect
 * - double_meta: Double meta refresh (hides referrer)
 * - js: JavaScript redirect
 * - iframe: Load in iframe
 * - frame: Load in frameset (old-school)
 * - blank_referrer: Load URL while blanking referrer
 * - remote: Fetch URL from remote server
 * - to_campaign: Redirect to another campaign
 * - show_html: Display HTML content
 * - show_text: Display plain text
 * - status404: Return 404
 * - do_nothing: Empty response
 */

import type { StageInterface, StageResult, ActionType, PipelinePayload } from '../types';

import { replaceMacros } from '../../macros';

// Cookie store for double_meta tracking (in-memory, should use Redis in production)
const doubleMetaCookies = new Map<string, { destination: string; expires: number }>();

export class ExecuteActionStage implements StageInterface {
  name = 'ExecuteActionStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const actionType = payload.actionType;
    const rawClick = payload.getRawClick();

    if (!rawClick) {
      return {
        success: false,
        payload,
        error: 'RawClick not set',
        abort: true
      };
    }

    if (!actionType) {
      // No action specified, do nothing
      payload.log('No action type specified, doing nothing');
      return { success: true, payload };
    }

    payload.log(`Executing action: ${actionType}`);

    // Get action payload and apply macros
    let actionPayload = payload.actionPayload || '';
    actionPayload = replaceMacros(actionPayload, {
      clickId: rawClick.clickId,
      campaignId: rawClick.campaignId || '',
      streamId: rawClick.streamId || '',
      pubId: rawClick.subId || '',
      subId: rawClick.subId || '',
      subId1: rawClick.subId1 || '',
      subId2: rawClick.subId2 || '',
      subId3: rawClick.subId3 || '',
      ip: rawClick.ipString,
      country: rawClick.country || '',
      region: rawClick.region || '',
      city: rawClick.city || '',
      browser: rawClick.browser || '',
      os: rawClick.os || '',
      device: rawClick.deviceType || '',
      userAgent: rawClick.userAgent,
      referrer: rawClick.referrer || '',
      keyword: rawClick.keyword || '',
      source: rawClick.source || '',
      timestamp: Math.floor(Date.now() / 1000).toString(),
      date: new Date().toISOString().split('T')[0],
    });

    // Execute based on action type
    switch (actionType) {
      case 'http_redirect':
        this.executeHttpRedirect(payload, actionPayload, 302);
        break;
      case 'http301':
        this.executeHttpRedirect(payload, actionPayload, 301);
        break;
      case 'meta':
        this.executeMetaRefresh(payload, actionPayload);
        break;
      case 'double_meta':
        await this.executeDoubleMetaRefresh(payload, actionPayload);
        break;
      case 'js':
        this.executeJsRedirect(payload, actionPayload);
        break;
      case 'iframe':
        this.executeIframe(payload, actionPayload);
        break;
      case 'frame':
        this.executeFrame(payload, actionPayload);
        break;
      case 'blank_referrer':
        this.executeBlankReferrer(payload, actionPayload);
        break;
      case 'remote':
        await this.executeRemoteRedirect(payload, actionPayload);
        break;
      case 'to_campaign':
        this.executeToCampaign(payload, actionPayload);
        break;
      case 'show_html':
        this.executeShowHtml(payload, actionPayload);
        break;
      case 'show_text':
        this.executeShowText(payload, actionPayload);
        break;
      case 'status404':
        this.executeStatus404(payload);
        break;
      case 'do_nothing':
        this.executeDoNothing(payload);
        break;
      default:
        this.executeHttpRedirect(payload, actionPayload, 302);
    }

    rawClick.destination = actionPayload;
    payload.log(`Action executed: ${actionType} -> ${actionPayload.substring(0, 100)}`);

    return {
      success: true,
      payload
    };
  }

  /**
   * HTTP redirect (302 or 301)
   */
  private executeHttpRedirect(payload: PipelinePayload, url: string, status: number): void {
    payload.setRedirect(url, status);
    payload.addHeader('Location', url);
    payload.addHeader('Referrer-Policy', 'no-referrer');
    payload.addHeader('X-RT', '1');
  }

  /**
   * Meta refresh redirect
   */
  private executeMetaRefresh(payload: PipelinePayload, url: string): void {
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="0; url='${this.escapeHtml(url)}'">
  <title>Redirecting...</title>
</head>
<body>
  <p>Redirecting to <a href="${this.escapeHtml(url)}">${this.escapeHtml(url)}</a></p>
</body>
</html>`;
    payload.setBody(html, 'text/html');
    payload.addHeader('Referrer-Policy', 'no-referrer');
  }

  /**
   * Double meta refresh (two consecutive meta refreshes to hide referrer)
   * 
   * How it works:
   * 1. First request: Set a cookie with the destination URL, meta refresh to same URL with parameter
   * 2. Second request: Detect parameter, read cookie, meta refresh to destination
   * 
   * This technique effectively hides the original referrer from the final destination.
   */
  private async executeDoubleMetaRefresh(payload: PipelinePayload, url: string): Promise<void> {
    const request = payload.request;
    if (!request) {
      // Fallback to regular meta refresh
      this.executeMetaRefresh(payload, url);
      return;
    }

    // Generate a unique session key for this double meta flow
    const sessionId = this.generateSessionId();
    const currentUrl = new URL(request.url);
    const dmrStep = currentUrl.searchParams.get('_dmr');
    
    if (dmrStep === '2') {
      // Second step: Read cookie and redirect to final destination
      const storedData = doubleMetaCookies.get(sessionId);
      
      if (storedData && storedData.expires > Date.now()) {
        // Clean up the cookie data
        doubleMetaCookies.delete(sessionId);
        
        // Return meta refresh to final destination (referrer is now blank)
        const html = `<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="0; url='${this.escapeHtml(storedData.destination)}'">
  <title>Redirecting...</title>
</head>
<body>
  <p>Redirecting to <a href="${this.escapeHtml(storedData.destination)}">${this.escapeHtml(storedData.destination)}</a></p>
</body>
</html>`;
        payload.setBody(html, 'text/html');
        payload.addHeader('Referrer-Policy', 'no-referrer');
        payload.log('Double meta refresh step 2: redirecting to final destination');
        return;
      }
    }

    // First step: Store destination and redirect to intermediate page
    // Store the destination URL with 60 second expiry
    doubleMetaCookies.set(sessionId, {
      destination: url,
      expires: Date.now() + 60000
    });

    // Build intermediate URL with step indicator
    const intermediateUrl = new URL(currentUrl.origin + currentUrl.pathname);
    intermediateUrl.searchParams.set('_dmr', '2');
    intermediateUrl.searchParams.set('_sid', sessionId);

    // Meta refresh to intermediate URL (first hop)
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="refresh" content="0; url='${this.escapeHtml(intermediateUrl.toString())}'">
  <title>Loading...</title>
</head>
<body>
  <p>Loading...</p>
</body>
</html>`;
    payload.setBody(html, 'text/html');
    payload.addHeader('Referrer-Policy', 'no-referrer');
    payload.log('Double meta refresh step 1: redirecting to intermediate URL');
  }

  /**
   * JavaScript redirect
   */
  private executeJsRedirect(payload: PipelinePayload, url: string): void {
    const html = `<!DOCTYPE html>
<html>
<head>
  <script>
    window.location.href = ${JSON.stringify(url)};
  </script>
</head>
<body>
  <p>Redirecting...</p>
</body>
</html>`;
    payload.setBody(html, 'text/html');
    payload.addHeader('Referrer-Policy', 'no-referrer');
  }

  /**
   * Iframe load
   */
  private executeIframe(payload: PipelinePayload, url: string): void {
    const html = `<!DOCTYPE html>
<html>
<head>
  <style>
    html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
    iframe { border: none; width: 100%; height: 100%; }
  </style>
</head>
<body>
  <iframe src="${this.escapeHtml(url)}"></iframe>
</body>
</html>`;
    payload.setBody(html, 'text/html');
    payload.addHeader('X-Frame-Options', 'SAMEORIGIN');
  }

  /**
   * Frame redirect (old-school frameset)
   * Similar to iframe but uses HTML frameset instead
   */
  private executeFrame(payload: PipelinePayload, url: string): void {
    const html = `<!DOCTYPE html>
<html>
<head>
  <title>Loading...</title>
  <style>
    html, body, frameset { margin: 0; padding: 0; height: 100%; width: 100%; }
  </style>
</head>
<frameset rows="100%" border="0" frameborder="0" framespacing="0">
  <frame src="${this.escapeHtml(url)}" noresize scrolling="auto" frameborder="0" marginwidth="0" marginheight="0">
</frameset>
<noframes>
  <body>
    <p>Your browser does not support frames. <a href="${this.escapeHtml(url)}">Click here to continue</a></p>
  </body>
</noframes>
</html>`;
    payload.setBody(html, 'text/html');
    payload.addHeader('X-Frame-Options', 'SAMEORIGIN');
    payload.log('Frame redirect: loading URL in frameset');
  }

  /**
   * Blank referrer - Load URL while blanking the referrer
   * Uses a combination of meta refresh and iframe techniques
   * 
   * This technique uses multiple methods to ensure the referrer is blank:
   * 1. Meta refresh to clear referrer
   * 2. HTTPS redirect (referrers aren't sent from HTTPS to HTTP)
   * 3. Data URI iframe for additional protection
   */
  private executeBlankReferrer(payload: PipelinePayload, url: string): void {
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Referrer-Policy" content="no-referrer">
  <title>Loading...</title>
  <style>
    html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
    iframe { border: none; width: 100%; height: 100%; position: absolute; top: 0; left: 0; }
  </style>
  <script>
    (function() {
      // Clear referrer using multiple techniques
      var url = ${JSON.stringify(url)};
      
      // Method 1: Use meta refresh inside a generated iframe
      var iframe = document.createElement('iframe');
      iframe.style.display = 'none';
      document.body.appendChild(iframe);
      
      // Write a meta refresh page inside the iframe
      var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
      iframeDoc.open();
      iframeDoc.write('<html><head>');
      iframeDoc.write('<meta name="referrer" content="no-referrer">');
      iframeDoc.write('<meta http-equiv="refresh" content="0;url=' + url + '">');
      iframeDoc.write('</head><body></body></html>');
      iframeDoc.close();
      
      // Method 2: Direct location change after a small delay
      setTimeout(function() {
        // Create a link with rel="noreferrer"
        var a = document.createElement('a');
        a.href = url;
        a.rel = 'noreferrer';
        a.target = '_top';
        document.body.appendChild(a);
        a.click();
        
        // Fallback: Direct location change
        setTimeout(function() {
          window.location.href = url;
        }, 100);
      }, 50);
    })();
  </script>
</head>
<body>
  <p style="text-align:center;padding-top:50px;">Loading...</p>
</body>
</html>`;
    payload.setBody(html, 'text/html');
    payload.addHeader('Referrer-Policy', 'no-referrer');
    payload.log('Blank referrer: loading URL with referrer blanking');
  }

  /**
   * Remote redirect - Fetch URL from remote server
   * 
   * Executes HTTP request to the actionPayload URL
   * Parses response as redirect URL
   * Redirects to that URL
   */
  private async executeRemoteRedirect(payload: PipelinePayload, remoteUrl: string): Promise<void> {
    try {
      payload.log(`Fetching remote URL: ${remoteUrl}`);
      
      const response = await fetch(remoteUrl, {
        method: 'GET',
        headers: {
          'User-Agent': 'Mozilla/5.0 (compatible; TDS/1.0)',
          'Accept': 'text/plain, text/html, application/json',
        },
        redirect: 'follow',
      });

      if (!response.ok) {
        payload.log(`Remote fetch failed: ${response.status} ${response.statusText}`);
        // Fallback to direct redirect
        this.executeHttpRedirect(payload, remoteUrl, 302);
        return;
      }

      // Try to get the redirect URL from response
      let redirectUrl: string | null = null;
      
      // Check if response is JSON
      const contentType = response.headers.get('content-type') || '';
      if (contentType.includes('application/json')) {
        const json = await response.json();
        // Common JSON fields for redirect URL
        redirectUrl = json.url || json.redirect || json.redirect_url || json.destination || json.link;
      } else {
        // Treat as plain text - get the URL from response body
        const text = await response.text();
        // Trim and check if it's a valid URL
        const trimmed = text.trim();
        if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
          redirectUrl = trimmed;
        } else {
          // Try to extract URL from HTML or other formats
          const urlMatch = trimmed.match(/https?:\/\/[^\s<>"']+/);
          if (urlMatch) {
            redirectUrl = urlMatch[0];
          }
        }
      }

      if (redirectUrl) {
        payload.log(`Remote redirect resolved to: ${redirectUrl}`);
        this.executeHttpRedirect(payload, redirectUrl, 302);
      } else {
        payload.log('Remote response did not contain a valid redirect URL');
        // Fallback to the remote URL itself
        this.executeHttpRedirect(payload, remoteUrl, 302);
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      payload.log(`Remote fetch error: ${errorMessage}`);
      // Fallback to direct redirect
      this.executeHttpRedirect(payload, remoteUrl, 302);
    }
  }

  /**
   * Redirect to another campaign
   * 
   * Sets payload.forcedCampaignId to the campaign ID from actionPayload
   * Aborts current pipeline to restart with new campaign
   */
  private executeToCampaign(payload: PipelinePayload, campaignId: string): void {
    if (!campaignId || campaignId.trim() === '') {
      payload.log('to_campaign: No campaign ID provided');
      this.executeDoNothing(payload);
      return;
    }

    // Set the forced campaign ID
    payload.forcedCampaignId = campaignId.trim();
    payload.log(`Redirecting to campaign: ${campaignId}`);
    
    // Abort current pipeline - the pipeline handler should restart with the new campaign
    payload.abort();
    
    // Set a special status to indicate restart needed
    payload.statusCode = 307; // Temporary Redirect
    payload.addHeader('X-TDS-Restart', '1');
    payload.addHeader('X-TDS-Campaign-Id', campaignId.trim());
  }

  /**
   * Show HTML content
   */
  private executeShowHtml(payload: PipelinePayload, html: string): void {
    payload.setBody(html, 'text/html');
  }

  /**
   * Show plain text
   */
  private executeShowText(payload: PipelinePayload, text: string): void {
    payload.setBody(text, 'text/plain');
  }

  /**
   * Return 404
   */
  private executeStatus404(payload: PipelinePayload): void {
    payload.statusCode = 404;
    payload.setBody('Not Found', 'text/plain');
  }

  /**
   * Empty response
   */
  private executeDoNothing(payload: PipelinePayload): void {
    payload.setBody('', 'text/plain');
    payload.statusCode = 200;
  }

  /**
   * Escape HTML
   */
  private escapeHtml(text: string): string {
    return text
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  /**
   * Generate a unique session ID for double meta refresh
   */
  private generateSessionId(): string {
    return Math.random().toString(36).substring(2, 15) + 
           Math.random().toString(36).substring(2, 15);
  }
}
