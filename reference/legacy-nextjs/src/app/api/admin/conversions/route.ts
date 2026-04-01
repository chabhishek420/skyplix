/**
 * Admin API - Conversions
 * View and manage conversions
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/conversions - List all conversions
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const status = searchParams.get('status') || 'all';
    const campaignId = searchParams.get('campaignId');
    const clickId = searchParams.get('clickId');
    const startDate = searchParams.get('startDate');
    const endDate = searchParams.get('endDate');
    const limit = parseInt(searchParams.get('limit') || '50', 10);
    const offset = parseInt(searchParams.get('offset') || '0', 10);

    const where: Record<string, unknown> = {};
    
    if (status !== 'all') {
      where.status = status;
    }
    
    if (campaignId) {
      where.campaignId = campaignId;
    }
    
    if (clickId) {
      where.clickId = clickId;
    }
    
    if (startDate || endDate) {
      where.postbackAt = {};
      if (startDate) {
        (where.postbackAt as Record<string, Date>).gte = new Date(startDate);
      }
      if (endDate) {
        (where.postbackAt as Record<string, Date>).lte = new Date(endDate);
      }
    }

    const [conversions, total, summary] = await Promise.all([
      db.conversion.findMany({
        where,
        include: {
          click: {
            select: {
              clickId: true,
              ip: true,
              country: true,
              city: true,
              deviceType: true,
              browser: true,
              os: true,
              isBot: true,
              clickedAt: true
            }
          },
          campaign: {
            select: {
              id: true,
              name: true,
              campaignId: true
            }
          },
          network: {
            select: {
              id: true,
              name: true
            }
          }
        },
        orderBy: { postbackAt: 'desc' },
        take: limit,
        skip: offset
      }),
      db.conversion.count({ where }),
      // Summary stats
      db.conversion.aggregate({
        where,
        _count: true,
        _sum: {
          payout: true,
          revenue: true
        }
      })
    ]);

    return NextResponse.json({
      success: true,
      conversions,
      total,
      summary: {
        count: summary._count,
        totalPayout: summary._sum.payout || 0,
        totalRevenue: summary._sum.revenue || 0
      },
      limit,
      offset
    });
  } catch (error) {
    console.error('Error fetching conversions:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch conversions' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/conversions - Update conversion status
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();

    if (!body.id) {
      return NextResponse.json(
        { success: false, error: 'Conversion ID is required' },
        { status: 400 }
      );
    }

    const updateData: Record<string, unknown> = {};
    
    if (body.status) {
      updateData.status = body.status;
    }
    
    if (typeof body.payout === 'number') {
      updateData.payout = body.payout;
    }
    
    if (typeof body.revenue === 'number') {
      updateData.revenue = body.revenue;
    }

    const conversion = await db.conversion.update({
      where: { id: body.id },
      data: updateData
    });

    return NextResponse.json({
      success: true,
      conversion
    });
  } catch (error) {
    console.error('Error updating conversion:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update conversion' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/conversions - Delete conversion
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Conversion ID is required' },
        { status: 400 }
      );
    }

    await db.conversion.delete({
      where: { id }
    });

    return NextResponse.json({
      success: true
    });
  } catch (error) {
    console.error('Error deleting conversion:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete conversion' },
      { status: 500 }
    );
  }
}
