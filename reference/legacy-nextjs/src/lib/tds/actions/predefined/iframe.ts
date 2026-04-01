/**
 * Iframe Redirect Action
 * Loads destination URL in an iframe
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

export class IframeRedirectAction extends AbstractAction {
  /**
   * Execute iframe redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for iframe'
      };
    }

    const frameWidth = this.options.frameWidth || '100%';
    const frameHeight = this.options.frameHeight || '100%';
    const frameBorder = this.options.frameBorder || '0';

    const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title></title>
  <style>
    * { margin: 0; padding: 0; }
    html, body { width: 100%; height: 100%; overflow: hidden; }
    iframe { width: ${frameWidth}; height: ${frameHeight}; border: none; }
  </style>
</head>
<body>
  <iframe src="${url}" frameborder="${frameBorder}" allowfullscreen></iframe>
</body>
</html>`;

    this.setBody(html);
    this.setStatus(200);
    this.setDestinationInfo(url);
    this.addLog(`Iframe redirect to: ${url}`);

    return {
      success: true,
      payload: this.payload
    };
  }
}

/**
 * Frame Redirect Action
 * Uses old-school frameset for redirect
 */
export class FrameRedirectAction extends AbstractAction {
  /**
   * Execute frame redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for frame'
      };
    }

    const html = `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title></title>
</head>
<frameset rows="100%,*" frameborder="0" border="0" framespacing="0">
  <frame src="${url}" noresize scrolling="auto">
</frameset>
<noframes>
  <body>
    <p>Your browser does not support frames.</p>
    <a href="${url}">Click here to continue</a>
  </body>
</noframes>
</html>`;

    this.setBody(html);
    this.setStatus(200);
    this.setDestinationInfo(url);
    this.addLog(`Frame redirect to: ${url}`);

    return {
      success: true,
      payload: this.payload
    };
  }
}
