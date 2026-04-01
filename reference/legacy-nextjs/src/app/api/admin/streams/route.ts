/**
 * Admin API - Streams
 * CRUD operations for streams
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const StreamSchema = z.object({
  campaignId: z.string().min(1, "Campaign ID is required"),
  name: z.string().min(1, "Name is required"),
  alias: z.string().optional(),
  type: z.enum(['regular', 'forced', 'fallback']).default('regular'),
  schema: z.string().default('url'),
  weight: z.number().min(0).max(100).default(100),
  position: z.number().default(0),
  status: z.enum(['active', 'paused', 'deleted']).default('active'),
  actionType: z.string().optional(),
  actionPayload: z.string().optional(),
  actionOptions: z.string().optional(),
  collectClicks: z.boolean().default(true),
  filterOr: z.boolean().default(false)
});

// GET /api/admin/streams - List all streams
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const campaignId = searchParams.get('campaignId');
    const status = searchParams.get('status') || 'active';
    const limit = parseInt(searchParams.get('limit') || '50', 10);
    const offset = parseInt(searchParams.get('offset') || '0', 10);

    const where: Record<string, unknown> = {};
    
    if (campaignId) {
      where.campaignId = campaignId;
    }
    
    if (status !== 'all') {
      where.status = status;
    }

    const [streams, total] = await Promise.all([
      db.stream.findMany({
        where,
        include: {
          campaign: {
            select: {
              id: true,
              name: true,
              campaignId: true
            }
          },
          filters: {
            select: {
              id: true,
              name: true,
              mode: true
            }
          },
          _count: {
            select: { clicks: true }
          }
        },
        orderBy: [
          { position: 'asc' },
          { weight: 'desc' }
        ],
        take: limit,
        skip: offset
      }),
      db.stream.count({ where })
    ]);

    return NextResponse.json({
      success: true,
      streams,
      total,
      limit,
      offset
    });
  } catch (error) {
    console.error('Error fetching streams:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch streams' },
      { status: 500 }
    );
  }
}

// POST /api/admin/streams - Create new stream
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = StreamSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ 
        success: false, 
        error: 'Validation failed', 
        details: result.error.format() 
      }, { status: 400 });
    }
    
    const body = result.data;
    
    // Verify campaign exists
    const campaign = await db.campaign.findUnique({
      where: { id: body.campaignId }
    });
    
    if (!campaign) {
      return NextResponse.json(
        { success: false, error: 'Campaign not found' },
        { status: 404 }
      );
    }

    const stream = await db.stream.create({
      data: {
        campaignId: body.campaignId,
        name: body.name,
        alias: body.alias,
        type: body.type || 'regular',
        schema: body.schema || 'url',
        weight: body.weight || 100,
        position: body.position || 0,
        status: body.status || 'active',
        actionType: body.actionType,
        actionPayload: body.actionPayload,
        actionOptions: body.actionOptions,
        collectClicks: body.collectClicks !== false,
        filterOr: body.filterOr || false
      }
    });

    return NextResponse.json({
      success: true,
      stream
    }, { status: 201 });
  } catch (error) {
    console.error('Error creating stream:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to create stream' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/streams - Update stream
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const { id, ...data } = json;

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Stream ID is required' },
        { status: 400 }
      );
    }

    const result = StreamSchema.partial().safeParse(data);
    if (!result.success) {
      return NextResponse.json({ 
        success: false, 
        error: 'Validation failed', 
        details: result.error.format() 
      }, { status: 400 });
    }
    
    const validatedData = result.data;
    
    const stream = await db.stream.update({
      where: { id },
      data: {
        name: validatedData.name,
        alias: validatedData.alias,
        type: validatedData.type,
        schema: validatedData.schema,
        weight: validatedData.weight,
        position: validatedData.position,
        status: validatedData.status,
        actionType: validatedData.actionType,
        actionPayload: validatedData.actionPayload,
        actionOptions: validatedData.actionOptions,
        collectClicks: validatedData.collectClicks,
        filterOr: validatedData.filterOr
      }
    });

    return NextResponse.json({
      success: true,
      stream
    });
  } catch (error) {
    console.error('Error updating stream:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update stream' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/streams - Delete stream
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const searchParams = request.nextUrl.searchParams;
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Stream ID is required' },
        { status: 400 }
      );
    }

    // Soft delete by setting status to deleted
    const stream = await db.stream.update({
      where: { id },
      data: { status: 'deleted' }
    });

    return NextResponse.json({
      success: true,
      stream
    });
  } catch (error) {
    console.error('Error deleting stream:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete stream' },
      { status: 500 }
    );
  }
}
