/**
 * DoNothingAction
 * 
 * Returns empty response (200 OK with no content).
 * Based on Keitaro's DoNothing.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class DoNothingAction extends BaseAction {
  name = 'do_nothing';
  weight = 1;

  async execute(): Promise<ActionResult> {
    // Return empty 200 response
    this.setBody('', 'text/plain');
    this.addLog('Do nothing: returning empty 200');

    return {
      success: true,
      payload: this.payload
    };
  }
}
