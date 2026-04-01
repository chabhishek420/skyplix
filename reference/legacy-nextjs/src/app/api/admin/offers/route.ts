/**
 * Admin API - Offers
 * CRUD operations for offers
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 * - Supports Api-Key header, Bearer token, or cookie session
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const OfferSchema = z.object({
  name: z.string().min(1, "Name is required"),
  url: z.string().url("Invalid offer URL").optional().or(z.literal("")),
  affiliateNetworkId: z.string().optional(),
  payoutValue: z.number().nonnegative().default(0),
  payoutCurrency: z.string().length(3).default('USD'),
  payoutType: z.enum(['CPA', 'CPC', 'CPL', 'CPS', 'RevShare']).default('CPA'),
  payoutAuto: z.boolean().default(false),
  conversionCapEnabled: z.boolean().default(false),
  dailyCap: z.number().nonnegative().optional(),
  alternativeOfferId: z.string().optional(),
  country: z.string().length(2).optional().transform(v => v?.toUpperCase()),
  actionType: z.string().optional(),
  actionPayload: z.string().optional(),
  actionOptions: z.string().optional(),
  offerType: z.enum(['external', 'local', 'landing']).default('external'),
  status: z.enum(['active', 'paused', 'archived', 'deleted']).default('active')
});

// GET /api/admin/offers - List all offers
export async function GET(request: NextRequest) {
  // Authentication check - matches Keitaro AdminApiContext._authorize()
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const status = searchParams.get('status') || 'active';
    const country = searchParams.get('country');
    const limit = parseInt(searchParams.get('limit') || '50', 10);
    const offset = parseInt(searchParams.get('offset') || '0', 10);

    const where: Record<string, unknown> = {};
    
    if (status !== 'all') {
      where.status = status;
    }
    
    if (country) {
      where.country = country.toUpperCase();
    }

    const [offers, total] = await Promise.all([
      db.offer.findMany({
        where,
        include: {
          streamAssociations: {
            select: {
              streamId: true,
              share: true,
              status: true
            }
          },
          _count: {
            select: { streamAssociations: true }
          }
        },
        orderBy: { createdAt: 'desc' },
        take: limit,
        skip: offset
      }),
      db.offer.count({ where })
    ]);

    return NextResponse.json({
      success: true,
      offers,
      total,
      limit,
      offset
    });
  } catch (error) {
    console.error('Error fetching offers:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch offers' },
      { status: 500 }
    );
  }
}

// POST /api/admin/offers - Create new offer
export async function POST(request: NextRequest) {
  // Authentication check
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = OfferSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ 
        success: false, 
        error: 'Validation failed', 
        details: result.error.format() 
      }, { status: 400 });
    }
    
    const body = result.data;

    const offer = await db.offer.create({
      data: {
        name: body.name,
        url: body.url,
        affiliateNetworkId: body.affiliateNetworkId,
        payoutValue: body.payoutValue,
        payoutCurrency: body.payoutCurrency,
        payoutType: body.payoutType,
        payoutAuto: body.payoutAuto,
        conversionCapEnabled: body.conversionCapEnabled,
        dailyCap: body.dailyCap,
        alternativeOfferId: body.alternativeOfferId,
        country: body.country,
        actionType: body.actionType,
        actionPayload: body.actionPayload,
        actionOptions: body.actionOptions,
        offerType: body.offerType,
        status: body.status
      }
    });

    return NextResponse.json({
      success: true,
      offer
    }, { status: 201 });
  } catch (error) {
    console.error('Error creating offer:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to create offer' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/offers - Update offer
export async function PUT(request: NextRequest) {
  // Authentication check
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const { id, ...data } = json;

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Offer ID is required' },
        { status: 400 }
      );
    }

    const result = OfferSchema.partial().safeParse(data);
    if (!result.success) {
      return NextResponse.json({ 
        success: false, 
        error: 'Validation failed', 
        details: result.error.format() 
      }, { status: 400 });
    }
    
    const validatedData = result.data;

    const offer = await db.offer.update({
      where: { id },
      data: {
        name: validatedData.name,
        url: validatedData.url,
        affiliateNetworkId: validatedData.affiliateNetworkId,
        payoutValue: validatedData.payoutValue,
        payoutCurrency: validatedData.payoutCurrency,
        payoutType: validatedData.payoutType,
        payoutAuto: validatedData.payoutAuto,
        conversionCapEnabled: validatedData.conversionCapEnabled,
        dailyCap: validatedData.dailyCap,
        alternativeOfferId: validatedData.alternativeOfferId,
        country: validatedData.country,
        actionType: validatedData.actionType,
        actionPayload: validatedData.actionPayload,
        actionOptions: validatedData.actionOptions,
        offerType: validatedData.offerType,
        status: validatedData.status
      }
    });

    return NextResponse.json({
      success: true,
      offer
    });
  } catch (error) {
    console.error('Error updating offer:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update offer' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/offers - Delete offer
export async function DELETE(request: NextRequest) {
  // Authentication check
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Offer ID is required' },
        { status: 400 }
      );
    }

    // Soft delete by setting status to deleted
    const offer = await db.offer.update({
      where: { id },
      data: { status: 'deleted' }
    });

    return NextResponse.json({
      success: true,
      offer
    });
  } catch (error) {
    console.error('Error deleting offer:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete offer' },
      { status: 500 }
    );
  }
}
