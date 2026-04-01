/**
 * Safe Page API
 *
 * Serves the "safe page" that bots and suspicious traffic see instead of
 * the real offer. This is the core of the cloaking mechanism.
 *
 * The CheckBotStage redirects detected bots to /safe (or a custom URL).
 * Without this route, all cloaked traffic returns 404.
 *
 * Mirrors Keitaro's SafePageController / safe page rendering:
 * - Returns a human-looking, innocuous page
 * - Customisable via the `safe_page_content` system setting
 * - Falls back to a minimal HTML stub if not configured
 *
 * Routes:
 *   GET /api/safe          — main safe page
 *   GET /api/safe/bot      — bot-specific safe page
 *   GET /api/safe/security — security scanner safe page
 *   GET /api/safe/verify   — suspicious traffic safe page
 *   GET /api/safe/debug    — debug traffic safe page
 *   GET /api/safe/error    — error/tool safe page
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';

// Default innocuous content served to bots
const DEFAULT_SAFE_HTML = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 20px; color: #333; }
    h1 { color: #444; }
  </style>
</head>
<body>
  <h1>Welcome</h1>
  <p>Thank you for visiting. This page is currently under maintenance.</p>
  <p>Please check back later.</p>
</body>
</html>`;

async function getSafePageContent(): Promise<string> {
  try {
    const setting = await db.setting.findUnique({ where: { key: 'safe_page_content' } });
    if (setting?.value) return setting.value;
  } catch {
    // DB not available — fall through to default
  }
  return DEFAULT_SAFE_HTML;
}

async function getSafePageUrl(): Promise<string | null> {
  try {
    const setting = await db.setting.findUnique({ where: { key: 'safe_page_url' } });
    return setting?.value || null;
  } catch {
    return null;
  }
}

export async function GET(request: NextRequest): Promise<NextResponse> {
  // Check for a globally configured safe page external URL
  const safeUrl = await getSafePageUrl();
  if (safeUrl) {
    return NextResponse.redirect(safeUrl, 302);
  }

  const content = await getSafePageContent();
  return new NextResponse(content, {
    status: 200,
    headers: { 'Content-Type': 'text/html; charset=utf-8' },
  });
}
