/**
 * FrameAction
 * 
 * Frameset redirect - loads URL in a full-page frame.
 * Based on Keitaro's Frame.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class FrameAction extends BaseAction {
  name = 'frame';
  weight = 2;

  async execute(): Promise<ActionResult> {
    const url = this.getProcessedPayload();

    // Generate frameset HTML
    const html = this.generateFrameHtml(url);

    this.setBody(html, 'text/html');
    this.addHeader('X-Frame-Options', 'SAMEORIGIN');
    this.setDestinationInfo(url);

    return {
      success: true,
      payload: this.payload
    };
  }

  /**
   * Generate frameset HTML
   */
  private generateFrameHtml(url: string): string {
    return `<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title></title>
  <style>
    * { margin: 0; padding: 0; }
    html, body, iframe { width: 100%; height: 100%; border: none; }
  </style>
</head>
<body>
  <iframe src="${this.escapeHtml(url)}" frameborder="0" marginwidth="0" marginheight="0" scrolling="auto" allowfullscreen></iframe>
  <script>
    // Prevent frame busting
    var preventFrameBusting = true;
    if (top != self) {
      top.location = self.location;
    }
  </script>
</body>
</html>`;
  }

  private escapeHtml(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
}
