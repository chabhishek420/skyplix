/**
 * SubId Action
 * Generate and manage sub IDs for tracking
 * Based on Keitaro's SubId.php action
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';

export class SubIdAction extends AbstractAction {
  name = 'sub_id';
  
  /**
   * Execute sub_id action
   * Generates unique sub ID for tracking
   */
  async execute(): Promise<ActionResult> {
    const url = this.getActionPayload();
    const rawClick = this.getRawClick();
    
    if (!url) {
      return {
        success: false,
        payload: this.payload,
        error: 'No URL specified for sub_id action',
        statusCode: 400
      };
    }
    
    // Generate sub ID from click ID or create new one
    const subId = rawClick?.subId || rawClick?.clickId?.substring(0, 16) || this.generateSubId();
    
    // Process URL with sub ID
    const processedUrl = this.processMacros(url);
    
    // Add sub_id parameter
    const finalUrl = this.addSubIdParam(processedUrl, subId);
    
    // Set redirect
    this.setRedirect(finalUrl, 302);
    
    return {
      success: true,
      payload: this.payload,
      statusCode: 302,
      body: ''
    };
  }
  
  /**
   * Add sub_id parameter to URL
   */
  private addSubIdParam(url: string, subId: string): string {
    try {
      const urlObj = new URL(url);
      urlObj.searchParams.set('sub_id', subId);
      return urlObj.toString();
    } catch {
      return url;
    }
  }
  
  /**
   * Generate random sub ID
   */
  private generateSubId(): string {
    const timestamp = Date.now().toString(36);
    const random = Math.random().toString(36).substring(2, 10);
    return `${timestamp}${random}`;
  }
}

export default SubIdAction;
