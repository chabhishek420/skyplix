/**
 * Postback API Endpoint
 * 
 * Handles conversion tracking postbacks from affiliate networks.
 * 
 * Supported methods: GET, POST
 * 
 * Parameters:
 * - clickid: Click ID from original redirect (required)
 * - status: approved/pending/rejected (required)
 * - payout: Conversion payout amount (optional)
 * - transaction_id: Unique transaction ID (optional)
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { isValidClickId, parseClickId } from '@/lib/tds';

export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);
  
  const clickId = searchParams.get('clickid');
  const status = searchParams.get('status') || 'approved';
  const payout = parseFloat(searchParams.get('payout') || '0');
  const transactionId = searchParams.get('transaction_id') || searchParams.get('txid');
  
  return processPostback({
    clickId,
    status,
    payout,
    transactionId,
    ip: getClientIp(request),
    rawParams: searchParams.toString()
  });
}

export async function POST(request: NextRequest) {
  try {
    const contentType = request.headers.get('content-type') || '';
    
    let clickId: string | null = null;
    let status = 'approved';
    let payout = 0;
    let transactionId: string | null = null;
    let rawParams = '';
    
    if (contentType.includes('application/json')) {
      const body = await request.json();
      clickId = body.clickid || body.click_id;
      status = body.status || 'approved';
      payout = parseFloat(body.payout || '0');
      transactionId = body.transaction_id || body.txid;
      rawParams = JSON.stringify(body);
    } else {
      // Form data or URL-encoded
      const formData = await request.formData();
      clickId = formData.get('clickid') as string || formData.get('click_id') as string;
      status = formData.get('status') as string || 'approved';
      payout = parseFloat(formData.get('payout') as string || '0');
      transactionId = formData.get('transaction_id') as string || formData.get('txid') as string;
      
      formData.forEach((value, key) => {
        rawParams += `${key}=${value}&`;
      });
    }
    
    return processPostback({
      clickId,
      status,
      payout,
      transactionId,
      ip: getClientIp(request),
      rawParams
    });
    
  } catch (error) {
    console.error('Postback error:', error);
    
    // Return 200 anyway to prevent enumeration
    return new NextResponse('', { status: 200 });
  }
}

/**
 * Process postback request
 */
async function processPostback(data: {
  clickId: string | null;
  status: string;
  payout: number;
  transactionId: string | null;
  ip: string;
  rawParams: string;
}): Promise<NextResponse> {
  
  // Always return 200 OK (prevents enumeration)
  const successResponse = () => new NextResponse('', { status: 200 });
  
  // Validate click ID
  if (!data.clickId || !isValidClickId(data.clickId)) {
    // Still return 200 to prevent enumeration
    return successResponse();
  }
  
  // Normalize status
  const normalizedStatus = normalizeStatus(data.status);
  
  try {
    // Find the click
    const click = await db.click.findUnique({
      where: { clickId: data.clickId },
      include: { campaign: true }
    });
    
    if (!click) {
      // Click not found - still return 200
      return successResponse();
    }
    
    // Check if conversion already exists
    const existingConversion = await db.conversion.findUnique({
      where: { clickId: data.clickId }
    });
    
    if (existingConversion) {
      // Update existing conversion
      await db.conversion.update({
        where: { id: existingConversion.id },
        data: {
          status: normalizedStatus,
          payout: data.payout,
          revenue: data.payout,
          transactionId: data.transactionId,
          postbackData: data.rawParams,
          postbackIp: data.ip,
          updatedAt: new Date()
        }
      });
    } else {
      // Create new conversion
      await db.conversion.create({
        data: {
          clickId: data.clickId,
          campaignId: click.campaignId,
          status: normalizedStatus,
          payout: data.payout,
          revenue: data.payout,
          transactionId: data.transactionId,
          postbackData: data.rawParams,
          postbackIp: data.ip,
          offerId: click.campaign?.offerId?.toString()
        }
      });
      
      // Update campaign stats
      if (click.campaignId && normalizedStatus === 'approved') {
        // Update publisher stats
        if (click.publisherId) {
          await db.publisher.update({
            where: { id: click.publisherId },
            data: { 
              totalConversions: { increment: 1 },
              totalRevenue: { increment: data.payout }
            }
          });
        }
      }
    }
    
    return successResponse();
    
  } catch (error) {
    console.error('Postback processing error:', error);
    return successResponse();
  }
}

/**
 * Normalize conversion status
 */
function normalizeStatus(status: string): string {
  const lower = status.toLowerCase();
  
  if (['approved', 'approved', 'approved', 'sale', 'confirmed', '1'].includes(lower)) {
    return 'approved';
  }
  
  if (['pending', 'waiting', 'processing', '2'].includes(lower)) {
    return 'pending';
  }
  
  if (['rejected', 'declined', 'cancelled', 'fraud', '0', '3'].includes(lower)) {
    return 'rejected';
  }
  
  return 'pending';
}

/**
 * Extract client IP from request
 */
function getClientIp(request: NextRequest): string {
  const cfIp = request.headers.get('cf-connecting-ip');
  if (cfIp) return cfIp;
  
  const forwarded = request.headers.get('x-forwarded-for');
  if (forwarded) {
    return forwarded.split(',')[0].trim();
  }
  
  return 'unknown';
}
