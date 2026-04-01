/**
 * Action Repository
 * Registry for all available actions
 * 
 * Based on Keitaro's StreamActionRepository.php
 */

import { AbstractAction } from './base';
import type { ActionResult } from './types';

// Import predefined actions
import { HttpRedirectAction, Http301RedirectAction } from './predefined/http-redirect';
import { MetaRedirectAction, DoubleMetaRedirectAction } from './predefined/meta';
import { IframeRedirectAction, FrameRedirectAction } from './predefined/iframe';
import { JsRedirectAction } from './predefined/js';
import { JsForIframeAction, JsForScriptAction } from './predefined/js-for-iframe';
import { ShowHtmlAction, ShowTextAction, Status404Action, DoNothingAction } from './predefined/content';
import { FrameAction } from './predefined/frame';
import { RemoteAction } from './predefined/remote';
import { CurlAction } from './predefined/curl';
import { FormSubmitAction } from './predefined/form-submit';
import { LocalFileAction } from './predefined/local-file';
import { ToCampaignAction } from './predefined/to-campaign';
import { SubIdAction } from './predefined/subid';
import { BlankReferrerAction } from './predefined/blank-referrer';

/**
 * Action info interface
 */
export interface ActionInfo {
  type: string;
  name: string;
  description?: string;
}

/**
 * Action Repository
 * Singleton pattern for managing actions
 */
class ActionRepository {
  private actions: Map<string, new () => AbstractAction> = new Map();
  private actionInfo: Map<string, ActionInfo> = new Map();

  constructor() {
    this.registerDefaults();
  }

  /**
   * Register default actions based on Keitaro
   */
  private registerDefaults(): void {
    // HTTP redirects
    this.registerAction('http_redirect', HttpRedirectAction, {
      type: 'http_redirect',
      name: 'HTTP 302 Redirect',
      description: 'Standard HTTP 302 redirect'
    });
    this.registerAction('http302', HttpRedirectAction, {
      type: 'http302',
      name: 'HTTP 302',
      description: 'HTTP 302 redirect'
    });
    this.registerAction('http301', Http301RedirectAction, {
      type: 'http301',
      name: 'HTTP 301 Redirect',
      description: 'Permanent HTTP 301 redirect'
    });
    
    // Meta refresh redirects
    this.registerAction('meta', MetaRedirectAction, {
      type: 'meta',
      name: 'Meta Refresh',
      description: 'HTML meta refresh redirect'
    });
    this.registerAction('double_meta', DoubleMetaRedirectAction, {
      type: 'double_meta',
      name: 'Double Meta Refresh',
      description: 'Two-stage meta refresh to hide referrer'
    });
    
    // Frame/iframe redirects
    this.registerAction('iframe', IframeRedirectAction, {
      type: 'iframe',
      name: 'Iframe',
      description: 'Load URL in iframe'
    });
    this.registerAction('frame', FrameAction, {
      type: 'frame',
      name: 'Frame',
      description: 'Frame-based redirect'
    });
    
    // JavaScript redirect
    this.registerAction('js', JsRedirectAction, {
      type: 'js',
      name: 'JavaScript Redirect',
      description: 'JavaScript window.location redirect'
    });
    this.registerAction('js_for_iframe', JsForIframeAction, {
      type: 'js_for_iframe',
      name: 'JS for Iframe',
      description: 'JavaScript redirect optimised for iframe contexts'
    });
    this.registerAction('js_for_script', JsForScriptAction, {
      type: 'js_for_script',
      name: 'JS for Script',
      description: 'JavaScript redirect served as application/javascript for <script src> contexts'
    });
    
    // Remote fetch
    this.registerAction('remote', RemoteAction, {
      type: 'remote',
      name: 'Remote',
      description: 'Fetch URL from remote and redirect'
    });
    this.registerAction('curl', CurlAction, {
      type: 'curl',
      name: 'cURL',
      description: 'Execute cURL request and show response'
    });
    
    // Form submit
    this.registerAction('form_submit', FormSubmitAction, {
      type: 'form_submit',
      name: 'Form Submit',
      description: 'Auto-submit form with POST data'
    });
    
    // Content display
    this.registerAction('show_html', ShowHtmlAction, {
      type: 'show_html',
      name: 'Show HTML',
      description: 'Display HTML content'
    });
    this.registerAction('show_text', ShowTextAction, {
      type: 'show_text',
      name: 'Show Text',
      description: 'Display plain text content'
    });
    
    // Status responses
    this.registerAction('status404', Status404Action, {
      type: 'status404',
      name: '404 Not Found',
      description: 'Return 404 status'
    });
    this.registerAction('do_nothing', DoNothingAction, {
      type: 'do_nothing',
      name: 'Do Nothing',
      description: 'Return empty response'
    });
    
    // Additional actions
    this.registerAction('local_file', LocalFileAction, {
      type: 'local_file',
      name: 'Local File',
      description: 'Serve a local file'
    });
    this.registerAction('to_campaign', ToCampaignAction, {
      type: 'to_campaign',
      name: 'To Campaign',
      description: 'Redirect to another campaign'
    });
    this.registerAction('sub_id', SubIdAction, {
      type: 'sub_id',
      name: 'Sub ID',
      description: 'Generate sub ID'
    });
    this.registerAction('blank_referrer', BlankReferrerAction, {
      type: 'blank_referrer',
      name: 'Blank Referrer',
      description: 'Load URL while blanking referrer'
    });
  }

  /**
   * Register an action with info
   */
  private registerAction(type: string, actionClass: new () => AbstractAction, info: ActionInfo): void {
    this.actions.set(type.toLowerCase(), actionClass);
    this.actionInfo.set(type.toLowerCase(), info);
  }

  /**
   * Get new action instance
   */
  getNewActionInstance(type: string): AbstractAction | null {
    const ActionClass = this.actions.get(type.toLowerCase());
    if (!ActionClass) {
      return null;
    }
    return new ActionClass();
  }

  /**
   * Check if action type exists
   */
  hasAction(type: string): boolean {
    return this.actions.has(type.toLowerCase());
  }

  /**
   * Get all registered action types
   */
  getActionTypes(): string[] {
    return Array.from(this.actions.keys());
  }

  /**
   * Get action info by type
   */
  getActionInfo(type: string): ActionInfo | null {
    return this.actionInfo.get(type.toLowerCase()) || null;
  }

  /**
   * Get all actions info
   */
  getAllActionsInfo(): ActionInfo[] {
    return Array.from(this.actionInfo.values());
  }
}

// Export singleton instance
export const actionRepository = new ActionRepository();

// Export for convenience
export { AbstractAction } from './base';
export type { ActionInterface, ActionResult } from './types';
