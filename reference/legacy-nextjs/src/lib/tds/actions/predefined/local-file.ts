/**
 * Local File Action
 * Serves a local file (landing page from upload folder)
 *
 * Based on Keitaro's LocalFile.php
 * PHP equivalent: Traffic\Actions\Predefined\LocalFile
 *
 * In PHP, this action uses a sandbox context to render a local PHP page.
 * In our Node/Next.js context, we serve the file contents directly (HTML/static).
 * PHP execution in local files is not supported in this implementation.
 */

import { AbstractAction } from '../base';
import type { ActionResult } from '../types';
import { readFileSync, existsSync } from 'fs';
import { join, resolve, extname } from 'path';

const UPLOAD_DIR = process.env.UPLOAD_DIR || join(process.cwd(), 'upload');

const MIME_TYPES: Record<string, string> = {
  '.html': 'text/html; charset=utf-8',
  '.htm':  'text/html; charset=utf-8',
  '.css':  'text/css',
  '.js':   'application/javascript',
  '.json': 'application/json',
  '.png':  'image/png',
  '.jpg':  'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif':  'image/gif',
  '.svg':  'image/svg+xml',
  '.ico':  'image/x-icon',
  '.txt':  'text/plain',
  '.pdf':  'application/pdf',
};

const NO_INDEX_FILE = 'Error: LP must contain index file. Please read the system log file.';

export class LocalFileAction extends AbstractAction {
  /**
   * Execute local file serving.
   *
   * The action payload is expected to be the folder name within UPLOAD_DIR.
   * We locate index.html (or index.htm) inside that folder and serve it.
   */
  async execute(): Promise<ActionResult> {
    const folder = this.getActionPayload() || this.options.path || this.options.filePath;

    if (!folder) {
      this.setBody(NO_INDEX_FILE, 'text/html');
      this.setStatus(500);
      this.setDestinationInfo('LP');
      this.addLog('LocalFileAction: no folder specified');
      return { success: false, payload: this.payload, error: 'No folder specified' };
    }

    // Resolve the landing folder — prevent path traversal
    const safeRoot = resolve(UPLOAD_DIR);
    const landingDir = resolve(join(safeRoot, String(folder)));

    if (!landingDir.startsWith(safeRoot)) {
      this.setBody('Forbidden', 'text/plain');
      this.setStatus(403);
      this.addLog(`LocalFileAction: path traversal attempt blocked: ${folder}`);
      return { success: false, payload: this.payload, error: 'Forbidden' };
    }

    // Look for an index file
    const indexCandidates = ['index.html', 'index.htm'];
    let indexPath: string | null = null;

    for (const candidate of indexCandidates) {
      const full = join(landingDir, candidate);
      if (existsSync(full)) {
        indexPath = full;
        break;
      }
    }

    if (!indexPath) {
      this.setBody(NO_INDEX_FILE, 'text/html');
      this.setStatus(500);
      this.setDestinationInfo('LP');
      this.addLog(`LocalFileAction: no index file found in folder: ${folder}`);
      return {
        success: false,
        payload: this.payload,
        error: NO_INDEX_FILE,
      };
    }

    try {
      let content = readFileSync(indexPath, 'utf-8');

      // Apply macro substitution to the landing page HTML
      content = this.processMacros(content);

      const ext = extname(indexPath).toLowerCase();
      const contentType = MIME_TYPES[ext] || 'text/html; charset=utf-8';

      this.setBody(content, contentType);
      this.setStatus(200);
      this.setDestinationInfo(`LP:${folder}`);
      this.addLog(`LocalFileAction: served ${indexPath}`);

      return { success: true, payload: this.payload };
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : String(err);
      this.setBody(NO_INDEX_FILE, 'text/html');
      this.setStatus(500);
      this.setDestinationInfo('LP');
      this.addLog(`LocalFileAction: read error — ${message}`);
      return { success: false, payload: this.payload, error: message };
    }
  }
}
