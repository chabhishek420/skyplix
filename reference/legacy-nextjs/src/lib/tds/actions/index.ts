/**
 * Actions System
 * Based on Keitaro TDS Actions Architecture
 * 
 * Supports various redirect and action types:
 * - http_redirect: Standard HTTP 302 redirect
 * - http301: Permanent HTTP 301 redirect
 * - meta: Meta refresh redirect
 * - double_meta: Double meta refresh (hides referrer)
 * - iframe: Load URL in iframe
 * - frame: Frame redirect
 * - js: JavaScript redirect
 * - show_html: Display HTML content
 * - show_text: Display plain text
 * - status404: Return 404 error
 * - do_nothing: Empty response
 */

export * from './types';
export * from './base';
export * from './repository';

// Export predefined actions
export * from './predefined/http-redirect';
export * from './predefined/meta';
export * from './predefined/iframe';
export * from './predefined/js';
export * from './predefined/content';
