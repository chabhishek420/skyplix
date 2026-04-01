/**
 * Action Types
 * Based on Keitaro TDS Action System
 */

import type { PipelinePayload, ActionType } from '../pipeline/types';

/**
 * Action execution result
 */
export interface ActionResult {
  success: boolean;
  payload: PipelinePayload;
  error?: string;
  statusCode?: number;
  headers?: Record<string, string>;
  body?: string;
  contentType?: string;
}

/**
 * Abstract action interface
 */
export interface ActionInterface {
  name: string;
  description: string;
  execute(payload: PipelinePayload): Promise<ActionResult>;
}

/**
 * Action options for various redirect types
 */
export interface ActionOptions {
  // HTTP redirect options
  statusCode?: number; // 301, 302, 303, 307, 308
  
  // Meta refresh options
  delay?: number; // Seconds before redirect
  
  // Frame options
  frameWidth?: string;
  frameHeight?: string;
  frameBorder?: string;
  
  // JavaScript options
  jsDelay?: number; // Milliseconds before redirect
  
  // Content options
  contentType?: string;
  
  // Remote fetch options
  timeout?: number; // Milliseconds
  headers?: Record<string, string>;
  
  // Local file options
  filePath?: string;
  path?: string;
  
  // ToCampaign options
  campaignId?: string | number;
  
  // Blank referrer options
  method?: string;
  
  // Generic options
  [key: string]: unknown;
}

/**
 * Redirect types from Keitaro
 */
export const REDIRECT_TYPES = {
  HTTP_REDIRECT: 'http_redirect',
  HTTP_301: 'http301',
  META: 'meta',
  DOUBLE_META: 'double_meta',
  IFRAME: 'iframe',
  FRAME: 'frame',
  JS: 'js',
  JS_FOR_IFRAME: 'js_for_iframe',
  JS_FOR_SCRIPT: 'js_for_script',
  REMOTE: 'remote',
  LOCAL_FILE: 'local_file',
  SHOW_HTML: 'show_html',
  SHOW_TEXT: 'show_text',
  STATUS_404: 'status404',
  DO_NOTHING: 'do_nothing',
  TO_CAMPAIGN: 'to_campaign',
  SUB_ID: 'sub_id',
  CURL: 'curl',
  FORM_SUBMIT: 'form_submit',
  BLANK_REFERRER: 'blank_referrer',
} as const;

/**
 * Get action type from string
 */
export function parseActionType(type: string): ActionType | null {
  const normalized = type.toLowerCase().replace(/-/g, '_');
  
  const validTypes: ActionType[] = [
    'remote',
    'http_redirect',
    'http301',
    'meta',
    'double_meta',
    'iframe',
    'frame',
    'js',
    'js_for_iframe',
    'js_for_script',
    'blank_referrer',
    'local_file',
    'show_html',
    'show_text',
    'status404',
    'do_nothing',
    'to_campaign',
    'sub_id',
    'curl',
    'form_submit',
  ];
  
  if (validTypes.includes(normalized as ActionType)) {
    return normalized as ActionType;
  }
  
  return null;
}
