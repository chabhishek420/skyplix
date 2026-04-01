/**
 * Admin API - Landings
 * CRUD operations for landing pages
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/landings - List all landing pages
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const status = searchParams.get('status') || 'active';
    const limit = parseInt(searchParams.get('limit') || '50', 10);
    const offset = parseInt(searchParams.get('offset') || '0', 10);

    const where: Record<string, unknown> = {};
    
    if (status !== 'all') {
      where.status = status;
    }

    const [landings, total] = await Promise.all([
      db.landing.findMany({
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
      db.landing.count({ where })
    ]);

    return NextResponse.json({
      success: true,
      landings,
      total,
      limit,
      offset
    });
  } catch (error) {
    console.error('Error fetching landings:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch landing pages' },
      { status: 500 }
    );
  }
}

// POST /api/admin/landings - Create new landing page
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();

    // Validate required fields
    if (!body.name) {
      return NextResponse.json(
        { success: false, error: 'Landing page name is required' },
        { status: 400 }
      );
    }

    const landing = await db.landing.create({
      data: {
        name: body.name,
        url: body.url,
        actionType: body.actionType,
        actionPayload: body.actionPayload,
        actionOptions: body.actionOptions,
        landingType: body.landingType || 'external',
        status: body.status || 'active'
      }
    });

    return NextResponse.json({
      success: true,
      landing
    }, { status: 201 });
  } catch (error) {
    console.error('Error creating landing:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to create landing page' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/landings - Update landing page
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();

    if (!body.id) {
      return NextResponse.json(
        { success: false, error: 'Landing page ID is required' },
        { status: 400 }
      );
    }

    const landing = await db.landing.update({
      where: { id: body.id },
      data: {
        name: body.name,
        url: body.url,
        actionType: body.actionType,
        actionPayload: body.actionPayload,
        actionOptions: body.actionOptions,
        landingType: body.landingType,
        status: body.status
      }
    });

    return NextResponse.json({
      success: true,
      landing
    });
  } catch (error) {
    console.error('Error updating landing:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update landing page' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/landings - Delete landing page
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Landing page ID is required' },
        { status: 400 }
      );
    }

    // Soft delete by setting status to deleted
    const landing = await db.landing.update({
      where: { id },
      data: { status: 'deleted' }
    });

    return NextResponse.json({
      success: true,
      landing
    });
  } catch (error) {
    console.error('Error deleting landing:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete landing page' },
      { status: 500 }
    );
  }
}
