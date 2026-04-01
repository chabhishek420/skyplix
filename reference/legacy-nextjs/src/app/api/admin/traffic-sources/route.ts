/**
 * Traffic Sources Admin API
 * CRUD operations for traffic source management
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/traffic-sources - List all traffic sources
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const type = searchParams.get('type');
    const status = searchParams.get('status') || 'active';

    const where: Record<string, unknown> = {};
    if (type) where.type = type;
    if (status !== 'all') where.status = status;

    const trafficSources = await db.trafficSource.findMany({
      where,
      include: {
        _count: {
          select: { campaigns: true }
        }
      },
      orderBy: { createdAt: 'desc' }
    });

    return NextResponse.json({
      success: true,
      data: trafficSources
    });
  } catch (error) {
    console.error('Error fetching traffic sources:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch traffic sources' },
      { status: 500 }
    );
  }
}

// POST /api/admin/traffic-sources - Create new traffic source
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    
    const trafficSource = await db.trafficSource.create({
      data: {
        name: body.name,
        trafficSourceId: body.trafficSourceId,
        type: body.type || 'general',
        keywordParam: body.keywordParam || 'keyword',
        costParam: body.costParam || 'cost',
        sourceParam: body.sourceParam || 'source',
        useReferrer: body.useReferrer ?? true,
        referrerTemplate: body.referrerTemplate,
        postbackUrl: body.postbackUrl,
        postbackType: body.postbackType || 'get',
        postbackParams: body.postbackParams ? JSON.stringify(body.postbackParams) : null,
        impressionUrl: body.impressionUrl,
        status: body.status || 'active'
      }
    });

    return NextResponse.json({
      success: true,
      data: trafficSource
    });
  } catch (error: unknown) {
    console.error('Error creating traffic source:', error);
    const prismaError = error as { code?: string };
    if (prismaError.code === 'P2002') {
      return NextResponse.json(
        { success: false, error: 'Traffic source ID already exists' },
        { status: 400 }
      );
    }
    return NextResponse.json(
      { success: false, error: 'Failed to create traffic source' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/traffic-sources - Update traffic source
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    const { id, ...data } = body;

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Traffic source ID is required' },
        { status: 400 }
      );
    }

    const trafficSource = await db.trafficSource.update({
      where: { id },
      data: {
        name: data.name,
        trafficSourceId: data.trafficSourceId,
        type: data.type,
        keywordParam: data.keywordParam,
        costParam: data.costParam,
        sourceParam: data.sourceParam,
        useReferrer: data.useReferrer,
        referrerTemplate: data.referrerTemplate,
        postbackUrl: data.postbackUrl,
        postbackType: data.postbackType,
        postbackParams: data.postbackParams ? JSON.stringify(data.postbackParams) : null,
        impressionUrl: data.impressionUrl,
        status: data.status
      }
    });

    return NextResponse.json({
      success: true,
      data: trafficSource
    });
  } catch (error) {
    console.error('Error updating traffic source:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update traffic source' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/traffic-sources - Delete traffic source
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Traffic source ID is required' },
        { status: 400 }
      );
    }

    // Delete campaign associations first
    await db.campaignTrafficSource.deleteMany({
      where: { trafficSourceId: id }
    });

    // Delete traffic source
    await db.trafficSource.delete({
      where: { id }
    });

    return NextResponse.json({
      success: true,
      message: 'Traffic source deleted'
    });
  } catch (error) {
    console.error('Error deleting traffic source:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete traffic source' },
      { status: 500 }
    );
  }
}
