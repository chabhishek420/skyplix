/**
 * HTTP Redirect Action
 * Standard HTTP 302/301 redirect
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

export class HttpRedirectAction extends AbstractAction {
  /**
   * Execute HTTP redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for redirect'
      };
    }

    // Default to 302 redirect
    const status = this.options.statusCode || 302;
    
    this.setRedirect(url, status);
    this.setDestinationInfo(url);
    this.addLog(`HTTP ${status} redirect to: ${url}`);

    return {
      success: true,
      payload: this.payload
    };
  }
}

export class Http301RedirectAction extends AbstractAction {
  /**
   * Execute HTTP 301 permanent redirect
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL provided for redirect'
      };
    }

    this.setRedirect(url, 301);
    this.setDestinationInfo(url);
    this.addLog(`HTTP 301 permanent redirect to: ${url}`);

    return {
      success: true,
      payload: this.payload
    };
  }
}
