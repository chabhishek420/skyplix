/**
 * Affiliate Networks Admin API
 * CRUD operations for affiliate network management
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/affiliate-networks - List all affiliate networks
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const status = searchParams.get('status') || 'active';

    const where: Record<string, unknown> = {};
    if (status !== 'all') where.status = status;

    const networks = await db.affiliateNetwork.findMany({
      where,
      include: {
        _count: {
          select: { 
            conversions: true,
            offers: true
          }
        }
      },
      orderBy: { name: 'asc' }
    });

    return NextResponse.json({
      success: true,
      data: networks
    });
  } catch (error) {
    console.error('Error fetching affiliate networks:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch affiliate networks' },
      { status: 500 }
    );
  }
}

// POST /api/admin/affiliate-networks - Create new affiliate network
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    
    const network = await db.affiliateNetwork.create({
      data: {
        name: body.name,
        baseUrl: body.baseUrl,
        apiKey: body.apiKey,
        apiSecret: body.apiSecret,
        clickParam: body.clickParam || 'clickid',
        payoutParam: body.payoutParam || 'payout',
        statusParam: body.statusParam || 'status',
        status: body.status || 'active'
      }
    });

    return NextResponse.json({
      success: true,
      data: network
    });
  } catch (error: unknown) {
    console.error('Error creating affiliate network:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to create affiliate network' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/affiliate-networks - Update affiliate network
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    const { id, ...data } = body;

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Affiliate network ID is required' },
        { status: 400 }
      );
    }

    const network = await db.affiliateNetwork.update({
      where: { id },
      data: {
        name: data.name,
        baseUrl: data.baseUrl,
        apiKey: data.apiKey,
        apiSecret: data.apiSecret,
        clickParam: data.clickParam,
        payoutParam: data.payoutParam,
        statusParam: data.statusParam,
        status: data.status
      }
    });

    return NextResponse.json({
      success: true,
      data: network
    });
  } catch (error) {
    console.error('Error updating affiliate network:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update affiliate network' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/affiliate-networks - Delete affiliate network
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Affiliate network ID is required' },
        { status: 400 }
      );
    }

    // Check for associated conversions
    const conversionCount = await db.conversion.count({
      where: { networkId: id }
    });

    if (conversionCount > 0) {
      return NextResponse.json(
        { success: false, error: `Cannot delete: ${conversionCount} conversions are associated with this network` },
        { status: 400 }
      );
    }

    // Delete affiliate network
    await db.affiliateNetwork.delete({
      where: { id }
    });

    return NextResponse.json({
      success: true,
      message: 'Affiliate network deleted'
    });
  } catch (error) {
    console.error('Error deleting affiliate network:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete affiliate network' },
      { status: 500 }
    );
  }
}
