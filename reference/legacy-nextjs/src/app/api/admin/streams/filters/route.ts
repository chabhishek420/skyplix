import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const StreamFilterSchema = z.object({
  streamId: z.string().min(1, "Stream ID is required"),
  name: z.string().min(1, "Filter name is required"), // geo, device, etc.
  mode: z.enum(['accept', 'reject']),
  payload: z.string().optional().nullable() // JSON config of filter
});

// GET /api/admin/streams/filters?stream_id=... - List all filters for a stream
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const streamId = searchParams.get('stream_id');
    
    if (!streamId) {
      return NextResponse.json({ success: false, error: 'stream_id query parameter is required' }, { status: 400 });
    }
    
    const filters = await db.streamFilter.findMany({
      where: { streamId },
      orderBy: { createdAt: 'desc' }
    });

    return NextResponse.json({ success: true, data: filters });
  } catch (error) {
    console.error('Error fetching stream filters:', error);
    return NextResponse.json({ success: false, error: 'Failed to fetch stream filters' }, { status: 500 });
  }
}

// POST /api/admin/streams/filters - Create a stream filter
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = StreamFilterSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }

    if (result.data.payload) {
      try {
        JSON.parse(result.data.payload);
      } catch (e) {
        return NextResponse.json({ success: false, error: 'payload must be a valid JSON string' }, { status: 400 });
      }
    }

    const filter = await db.streamFilter.create({
      data: result.data
    });

    return NextResponse.json({ success: true, data: filter });
  } catch (error: any) {
    console.error('Error creating stream filter:', error);
    if (error.code === 'P2003') {
      return NextResponse.json({ success: false, error: 'Stream does not exist' }, { status: 400 });
    }
    return NextResponse.json({ success: false, error: 'Failed to create stream filter' }, { status: 500 });
  }
}

// PUT /api/admin/streams/filters - Update a stream filter
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Filter ID is required in query' }, { status: 400 });
    }
    
    const json = await request.json();
    const result = StreamFilterSchema.partial().safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }

    if (result.data.payload) {
      try {
        JSON.parse(result.data.payload);
      } catch (e) {
        return NextResponse.json({ success: false, error: 'payload must be a valid JSON string' }, { status: 400 });
      }
    }
    
    const filter = await db.streamFilter.update({
      where: { id },
      data: result.data
    });

    return NextResponse.json({ success: true, data: filter });
  } catch (error: any) {
    console.error('Error updating stream filter:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Stream filter not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to update stream filter' }, { status: 500 });
  }
}

// DELETE /api/admin/streams/filters - Delete a stream filter
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Filter ID is required' }, { status: 400 });
    }
    
    await db.streamFilter.delete({
      where: { id }
    });

    return NextResponse.json({ success: true, message: 'Stream filter deleted successfully' });
  } catch (error: any) {
    console.error('Error deleting stream filter:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Stream filter not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to delete stream filter' }, { status: 500 });
  }
}
