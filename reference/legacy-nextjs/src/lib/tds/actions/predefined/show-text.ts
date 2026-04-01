/**
 * ShowTextAction
 * 
 * Displays plain text content.
 * Based on Keitaro's ShowText.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class ShowTextAction extends BaseAction {
  name = 'show_text';
  weight = 1;

  async execute(): Promise<ActionResult> {
    const rawContent = this.payload.actionPayload || '';

    // Process macros in content
    const content = this.processMacros(rawContent);

    this.setBody(content, 'text/plain');

    return {
      success: true,
      payload: this.payload
    };
  }
}
