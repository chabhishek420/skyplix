/**
 * FormSubmitAction
 * 
 * Auto-submits a form with POST data.
 * Based on Keitaro's FormSubmit.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class FormSubmitAction extends BaseAction {
  name = 'form_submit';
  weight = 3;

  async execute(): Promise<ActionResult> {
    const rawPayload = this.payload.actionPayload || '';

    // Parse URL and form data from payload
    // Format: "url|field1=value1&field2=value2" or just URL
    const [url, formDataStr] = this.parsePayload(rawPayload);

    // Process macros
    const processedUrl = this.processMacros(url);
    const formData = formDataStr ? this.processMacros(formDataStr) : this.getDefaultFormData();

    // Generate form submit HTML
    const html = this.generateFormHtml(processedUrl, formData);

    this.setBody(html, 'text/html');
    this.setDestinationInfo(processedUrl);

    return {
      success: true,
      payload: this.payload
    };
  }

  /**
   * Parse payload into URL and form data
   */
  private parsePayload(rawPayload: string): [string, string | null] {
    const separatorIndex = rawPayload.indexOf('|');
    if (separatorIndex === -1) {
      return [rawPayload, null];
    }
    return [
      rawPayload.substring(0, separatorIndex),
      rawPayload.substring(separatorIndex + 1)
    ];
  }

  /**
   * Get default form data from click parameters
   */
  private getDefaultFormData(): string {
    const rawClick = this.payload.rawClick;
    if (!rawClick) return '';

    const fields: Record<string, string> = {
      clickid: rawClick.clickId,
      sub_id: rawClick.subId || '',
      source: rawClick.source || '',
      keyword: rawClick.keyword || '',
      country: rawClick.country || '',
      city: rawClick.city || '',
      device_type: rawClick.deviceType || '',
      os: rawClick.os || '',
      browser: rawClick.browser || ''
    };

    return Object.entries(fields)
      .filter(([_, v]) => v)
      .map(([k, v]) => `${k}=${encodeURIComponent(v)}`)
      .join('&');
  }

  /**
   * Generate auto-submit form HTML
   */
  private generateFormHtml(url: string, formData: string): string {
    const fields = this.parseFormData(formData);

    return `<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Redirecting...</title>
</head>
<body>
  <form id="autoForm" method="POST" action="${this.escapeHtml(url)}">
${fields.map(([name, value]) => `    <input type="hidden" name="${this.escapeHtml(name)}" value="${this.escapeHtml(value)}">`).join('\n')}
    <noscript>
      <p>Click continue to proceed.</p>
      <input type="submit" value="Continue">
    </noscript>
  </form>
  <script>
    document.getElementById('autoForm').submit();
  </script>
</body>
</html>`;
  }

  /**
   * Parse form data string into key-value pairs
   */
  private parseFormData(formData: string): [string, string][] {
    return formData.split('&').map(pair => {
      const [name, value] = pair.split('=');
      return [name, decodeURIComponent(value || '')];
    });
  }

  private escapeHtml(str: string): string {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
}
