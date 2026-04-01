/**
 * BlankReferrer Action
 * Load URL while blanking the referrer
 * Based on Keitaro's BlankReferrer.php action
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

export class BlankReferrerAction extends AbstractAction {
  name = 'blank_referrer';
  
  /**
   * Execute blank_referrer action
   * Uses multiple techniques to hide referrer
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    const options = this.options;
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL specified for blank referrer redirect',
        statusCode: 400
      };
    }
    
    const method = (options.method as string) || 'meta';
    
    switch (method) {
      case 'meta':
        return this.metaBlankReferrer(url);
      case 'https':
        return this.httpsBlankReferrer(url);
      case 'data':
        return this.dataBlankReferrer(url);
      case 'iframe':
        return this.iframeBlankReferrer(url);
      default:
        return this.metaBlankReferrer(url);
    }
  }
  
  /**
   * Meta refresh blank referrer
   * Uses meta refresh with referrer-policy
   */
  private metaBlankReferrer(url: string): ActionResult {
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta name="referrer" content="no-referrer">
  <meta http-equiv="refresh" content="0;url=${this.escapeHtml(url)}">
  <title>Redirecting...</title>
</head>
<body>
  <p>Redirecting...</p>
  <script>window.location.href = ${JSON.stringify(url)};</script>
</body>
</html>`;
    
    this.setBody(html, 'text/html');
    this.addHeader('Referrer-Policy', 'no-referrer');
    
    return {
      success: true,
      payload: this.payload,
      statusCode: 200,
      contentType: 'text/html',
      body: html
    };
  }
  
  /**
   * HTTPS blank referrer
   * Redirect from HTTPS to HTTP blanks referrer
   */
  private httpsBlankReferrer(url: string): ActionResult {
    const intermediateUrl = this.generateIntermediateUrl(url);
    this.setRedirect(intermediateUrl, 302);
    this.addHeader('Referrer-Policy', 'no-referrer');
    
    return {
      success: true,
      payload: this.payload,
      statusCode: 302,
      body: ''
    };
  }
  
  /**
   * Data URI blank referrer
   * Uses data URI to blank referrer
   */
  private dataBlankReferrer(url: string): ActionResult {
    const redirectHtml = `<html><head><meta http-equiv="refresh" content="0;url=${this.escapeHtml(url)}"></head><body></body></html>`;
    const dataUri = `data:text/html;base64,${Buffer.from(redirectHtml).toString('base64')}`;
    
    const html = `<!DOCTYPE html>
<html>
<head>
  <title>Redirecting...</title>
</head>
<body>
  <iframe src="${this.escapeHtml(dataUri)}" style="width:100%;height:100%;border:none;"></iframe>
</body>
</html>`;
    
    this.setBody(html, 'text/html');
    
    return {
      success: true,
      payload: this.payload,
      statusCode: 200,
      contentType: 'text/html',
      body: html
    };
  }
  
  /**
   * Iframe blank referrer
   * Uses iframe to load URL
   */
  private iframeBlankReferrer(url: string): ActionResult {
    const html = `<!DOCTYPE html>
<html>
<head>
  <meta name="referrer" content="no-referrer">
  <title>Loading...</title>
  <style>
    html, body { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; }
    iframe { width: 100%; height: 100%; border: none; }
  </style>
</head>
<body>
  <iframe src="${this.escapeHtml(url)}" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>
</body>
</html>`;
    
    this.setBody(html, 'text/html');
    this.addHeader('Referrer-Policy', 'no-referrer');
    
    return {
      success: true,
      payload: this.payload,
      statusCode: 200,
      contentType: 'text/html',
      body: html
    };
  }
  
  /**
   * Generate intermediate URL for HTTPS blanking
   */
  private generateIntermediateUrl(targetUrl: string): string {
    return `/redirect/blank?target=${encodeURIComponent(targetUrl)}`;
  }
  
  /**
   * Escape HTML entities
   */
  private escapeHtml(str: string): string {
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
}

export default BlankReferrerAction;
