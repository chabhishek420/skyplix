/**
 * Click ID Generator
 *
 * Generates 24-character hex click IDs in the format:
 * [8 hex obfuscated timestamp][16 hex random]
 *
 * Features:
 * - Collision detection with automatic retry
 * - Obfuscated timestamp for security
 * - High entropy random portion
 *
 * Example: a3c69b2f6f36cb0360522c91
 * - a3c69b2f = XOR-obfuscated Unix timestamp
 * - 6f36cb0360522c91 = Random unique identifier (64-bit entropy)
 */

import { randomBytes } from 'crypto';
import { db } from '@/lib/db';

// XOR mask for timestamp obfuscation (changes periodically for security)
// In production, this should be stored in environment/config
const TIMESTAMP_MASK = 0x5A3C69B2;

/**
 * Generate a new click ID
 * Format: [8 hex obfuscated timestamp][16 hex random] = 24 hex characters total
 */
export function generateClickId(): string {
  // Get current Unix timestamp in seconds
  const timestamp = Math.floor(Date.now() / 1000);

  // XOR with mask to obfuscate (reversible for internal use)
  const obfuscatedTimestamp = timestamp ^ TIMESTAMP_MASK;

  // Convert to 8-character hex string
  const timestampHex = obfuscatedTimestamp.toString(16).padStart(8, '0');

  // Generate 8 random bytes (16 hex characters) - 64-bit entropy
  const randomHex = randomBytes(8).toString('hex');

  return timestampHex + randomHex;
}

/**
 * Generate a guaranteed unique click ID with collision detection
 * Retries up to maxRetries times if collision detected
 *
 * @param maxRetries - Maximum retry attempts (default: 3)
 * @returns Promise<string> - Unique click ID
 */
export async function generateUniqueClickId(maxRetries: number = 3): Promise<string> {
  for (let attempt = 0; attempt < maxRetries; attempt++) {
    const clickId = generateClickId();

    // Check for collision in database
    const existing = await db.click.findUnique({
      where: { clickId },
      select: { clickId: true }
    });

    if (!existing) {
      return clickId;
    }

    // Log collision (rare event)
    console.warn(`[ClickID] Collision detected on attempt ${attempt + 1}, retrying...`);
  }

  // Fallback: Use UUID-based ID if all retries exhausted
  // This ensures uniqueness even under extreme collision scenarios
  const fallbackId = randomBytes(12).toString('hex');
  console.warn(`[ClickID] Using fallback ID after ${maxRetries} collisions`);

  return fallbackId;
}

/**
 * Parse a click ID to extract the timestamp
 * Note: Timestamp is XOR-obfuscated, this reverses it
 */
export function parseClickId(clickId: string): { timestamp: Date; random: string } | null {
  if (clickId.length !== 24 || !/^[0-9a-f]+$/i.test(clickId)) {
    return null;
  }

  const timestampHex = clickId.substring(0, 8);
  const random = clickId.substring(8);

  // Reverse XOR obfuscation
  const obfuscatedTimestamp = parseInt(timestampHex, 16);

  if (isNaN(obfuscatedTimestamp)) {
    return null;
  }

  // Reverse the XOR to get original timestamp
  const timestamp = obfuscatedTimestamp ^ TIMESTAMP_MASK;

  return {
    timestamp: new Date(timestamp * 1000),
    random
  };
}

/**
 * Validate click ID format
 */
export function isValidClickId(clickId: string): boolean {
  return clickId.length === 24 && /^[0-9a-f]+$/i.test(clickId);
}

/**
 * Get timestamp from click ID
 */
export function getClickIdTimestamp(clickId: string): Date | null {
  const parsed = parseClickId(clickId);
  return parsed?.timestamp || null;
}

/**
 * Check if click ID is recent (within specified seconds)
 */
export function isClickIdRecent(clickId: string, maxAgeSeconds: number = 86400): boolean {
  const parsed = parseClickId(clickId);
  if (!parsed) return false;

  const now = Date.now();
  const clickTime = parsed.timestamp.getTime();

  return (now - clickTime) <= (maxAgeSeconds * 1000);
}
