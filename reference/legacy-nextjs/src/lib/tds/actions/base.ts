/**
 * Base Action Class
 * Abstract base for all redirect actions
 */

import type { PipelinePayload, ActionType, RawClick } from '../pipeline/types';
import type { ActionResult, ActionOptions } from './types';
import { processMacros } from '../macros/processor';

/**
 * Abstract Action Base
 * All actions extend this class
 */
export abstract class AbstractAction {
  protected payload!: PipelinePayload;
  protected options: ActionOptions = {};
  protected log: string[] = [];

  /**
   * Set pipeline payload
   */
  setPipelinePayload(payload: PipelinePayload): this {
    this.payload = payload;
    return this;
  }

  /**
   * Get pipeline payload
   */
  getPipelinePayload(): PipelinePayload {
    return this.payload;
  }

  /**
   * Set action options
   */
  setOptions(options: ActionOptions): this {
    this.options = { ...this.options, ...options };
    return this;
  }

  /**
   * Get action payload (URL or content)
   */
  getActionPayload(): string | null {
    return this.payload.actionPayload;
  }

  /**
   * Get action type
   */
  getActionType(): ActionType | null {
    return this.payload.actionType;
  }

  /**
   * Get server request
   */
  getServerRequest() {
    return this.payload.request;
  }

  /**
   * Get raw click
   */
  getRawClick() {
    return this.payload.rawClick;
  }

  /**
   * Add header to response
   */
  addHeader(name: string, value: string): void {
    this.payload.headers[name] = value;
  }

  /**
   * Set HTTP status code
   */
  setStatus(code: number): void {
    this.payload.statusCode = code;
  }

  /**
   * Set response body
   */
  setBody(body: string, contentType: string = 'text/html'): void {
    this.payload.body = body;
    this.payload.contentType = contentType;
    this.payload.headers['Content-Type'] = contentType;
  }

  /**
   * Set redirect URL
   */
  setRedirect(url: string, status: number = 302): void {
    this.payload.redirectUrl = url;
    this.payload.statusCode = status;
    this.addHeader('Location', url);
  }

  /**
   * Set destination info (for tracking)
   */
  setDestinationInfo(url: string): void {
    if (this.payload.rawClick) {
      this.payload.rawClick.destination = url;
    }
  }

  /**
   * Log message
   */
  addLog(message: string): void {
    this.log.push(message);
    this.payload.logs.push(`[Action] ${message}`);
  }

  /**
   * Process macros in URL/content
   */
  processMacros(content: string): string {
    if (!this.payload.rawClick) return content;
    return processMacros(content, {
      rawClick: this.payload.rawClick,
      campaign: this.payload.campaign,
      stream: this.payload.stream,
      landing: this.payload.landing,
      offer: this.payload.offer,
      conversion: null,
      params: this.payload.getAllParams ? this.payload.getAllParams() : {}
    });
  }

  /**
   * Get processed payload (URL with macros replaced)
   * Alias for processMacros for backward compatibility
   */
  getProcessedPayload(): string {
    const url = this.payload.actionPayload || '';
    return this.processMacros(url);
  }

  /**
   * Get execution context from request
   */
  getExecutionContext(): 'script' | 'frame' | 'default' {
    if (!this.payload.request) return 'default';
    const url = new URL(this.payload.request.url);
    const frm = url.searchParams.get('frm');
    if (frm === 'script') return 'script';
    if (frm === 'frame') return 'frame';
    return 'default';
  }

  /**
   * Execute the action
   * Must be implemented by child classes
   */
  abstract execute(): Promise<ActionResult>;

  /**
   * Run the action (called by pipeline)
   */
  async run(): Promise<PipelinePayload> {
    const result = await this.execute();
    return result.payload;
  }
}

/**
 * BaseAction alias for backward compatibility
 */
export { AbstractAction as BaseAction };
