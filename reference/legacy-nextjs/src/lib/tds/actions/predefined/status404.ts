/**
 * Status404Action
 * 
 * Returns 404 Not Found response.
 * Based on Keitaro's Status404.php
 */

import { BaseAction } from '../base';
import type { ActionResult } from '../types';

export class Status404Action extends BaseAction {
  name = 'status404';
  weight = 1;

  async execute(): Promise<ActionResult> {
    // Set 404 status
    this.setStatus(404);
    this.setBody(this.get404Content(), 'text/html');

    // Log this action
    this.addLog('Returning 404 Not Found');

    return {
      success: true,
      payload: this.payload
    };
  }

  /**
   * Get 404 HTML content
   */
  private get404Content(): string {
    return `<!DOCTYPE html>
<html>
<head>
  <title>404 Not Found</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      padding: 50px;
    }
    h1 {
      font-size: 48px;
      color: #333;
    }
    p {
      color: #666;
    }
  </style>
</head>
<body>
  <h1>404</h1>
  <p>The page you are looking for does not exist.</p>
</body>
</html>`;
  }
}
