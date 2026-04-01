/**
 * Admin API - Domains
 * CRUD operations for domains
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/domains - List all domains
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const status = searchParams.get('status') || 'active';
    const type = searchParams.get('type');
    const limit = parseInt(searchParams.get('limit') || '50', 10);
    const offset = parseInt(searchParams.get('offset') || '0', 10);

    const where: Record<string, unknown> = {};
    
    if (status !== 'all') {
      where.status = status;
    }
    
    if (type) {
      where.type = type;
    }

    const [domains, total] = await Promise.all([
      db.domain.findMany({
        where,
        include: {
          campaign: {
            select: {
              id: true,
              name: true,
              campaignId: true
            }
          },
          landing: {
            select: {
              id: true,
              name: true
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        take: limit,
        skip: offset
      }),
      db.domain.count({ where })
    ]);

    return NextResponse.json({
      success: true,
      domains,
      total,
      limit,
      offset
    });
  } catch (error) {
    console.error('Error fetching domains:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch domains' },
      { status: 500 }
    );
  }
}

// POST /api/admin/domains - Create new domain
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();

    // Validate required fields
    if (!body.name) {
      return NextResponse.json(
        { success: false, error: 'Domain name is required' },
        { status: 400 }
      );
    }

    // Check if domain already exists
    const existing = await db.domain.findUnique({
      where: { name: body.name }
    });

    if (existing) {
      return NextResponse.json(
        { success: false, error: 'Domain already exists' },
        { status: 400 }
      );
    }

    const domain = await db.domain.create({
      data: {
        name: body.name.toLowerCase(),
        type: body.type || 'campaign',
        campaignId: body.campaignId,
        landingId: body.landingId,
        sslEnabled: body.sslEnabled !== false,
        sslAutoRenew: body.sslAutoRenew !== false,
        isDefault: body.isDefault || false,
        redirectToWww: body.redirectToWww || false,
        catchAll: body.catchAll || false,
        status: body.status || 'active'
      }
    });

    return NextResponse.json({
      success: true,
      domain
    }, { status: 201 });
  } catch (error) {
    console.error('Error creating domain:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to create domain' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/domains - Update domain
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();

    if (!body.id) {
      return NextResponse.json(
        { success: false, error: 'Domain ID is required' },
        { status: 400 }
      );
    }

    const domain = await db.domain.update({
      where: { id: body.id },
      data: {
        type: body.type,
        campaignId: body.campaignId,
        landingId: body.landingId,
        sslEnabled: body.sslEnabled,
        sslAutoRenew: body.sslAutoRenew,
        isDefault: body.isDefault,
        redirectToWww: body.redirectToWww,
        catchAll: body.catchAll,
        status: body.status
      }
    });

    return NextResponse.json({
      success: true,
      domain
    });
  } catch (error) {
    console.error('Error updating domain:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update domain' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/domains - Delete domain
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Domain ID is required' },
        { status: 400 }
      );
    }

    await db.domain.delete({
      where: { id }
    });

    return NextResponse.json({
      success: true
    });
  } catch (error) {
    console.error('Error deleting domain:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete domain' },
      { status: 500 }
    );
  }
}
