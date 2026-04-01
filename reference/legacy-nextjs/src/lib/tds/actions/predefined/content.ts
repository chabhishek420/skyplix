/**
 * Content Actions
 * Show HTML, show text, 404, and do nothing actions
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

/**
 * Show HTML Action
 * Displays custom HTML content
 */
export class ShowHtmlAction extends AbstractAction {
  async execute(): Promise<ActionResult> {
    const html = this.getActionPayload();
    
    if (!html) {
      return {
        success: false,
        payload: this.payload,
        error: 'No HTML content provided'
      };
    }

    this.setBody(html, 'text/html');
    this.setStatus(200);
    this.addLog('Showing HTML content');

    return {
      success: true,
      payload: this.payload
    };
  }
}

/**
 * Show Text Action
 * Displays plain text content
 */
export class ShowTextAction extends AbstractAction {
  async execute(): Promise<ActionResult> {
    const text = this.getActionPayload();
    
    if (!text) {
      return {
        success: false,
        payload: this.payload,
        error: 'No text content provided'
      };
    }

    this.setBody(text, 'text/plain');
    this.setStatus(200);
    this.addLog('Showing text content');

    return {
      success: true,
      payload: this.payload
    };
  }
}

/**
 * Status 404 Action
 * Returns a 404 Not Found response
 */
export class Status404Action extends AbstractAction {
  async execute(): Promise<ActionResult> {
    const customHtml = this.getActionPayload();
    
    const html = customHtml || `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>404 Not Found</title>
  <style>
    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
    h1 { color: #333; }
  </style>
</head>
<body>
  <h1>404 - Not Found</h1>
  <p>The requested resource could not be found.</p>
</body>
</html>`;

    this.setBody(html, 'text/html');
    this.setStatus(404);
    this.addLog('Returning 404 Not Found');

    return {
      success: true,
      payload: this.payload
    };
  }
}

/**
 * Do Nothing Action
 * Returns empty response
 */
export class DoNothingAction extends AbstractAction {
  async execute(): Promise<ActionResult> {
    this.setBody('', 'text/plain');
    this.setStatus(204); // No Content
    this.addLog('Doing nothing (empty response)');

    return {
      success: true,
      payload: this.payload
    };
  }
}
