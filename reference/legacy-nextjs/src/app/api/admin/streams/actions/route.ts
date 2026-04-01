import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const StreamActionSchema = z.object({
  stream_id: z.string().min(1, "Stream ID is required"),
  actionType: z.string().min(1, "Action Type is required"),
  actionPayload: z.string().optional().nullable(),
  actionOptions: z.string().optional().nullable() // Expect JSON string
});

// GET /api/admin/streams/actions - Get stream action configuration
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const streamId = searchParams.get('stream_id');
    
    if (!streamId) {
      return NextResponse.json({ success: false, error: 'stream_id is required' }, { status: 400 });
    }
    
    const stream = await db.stream.findUnique({
      where: { id: streamId },
      select: { id: true, actionType: true, actionPayload: true, actionOptions: true }
    });

    if (!stream) {
      return NextResponse.json({ success: false, error: 'Stream not found' }, { status: 404 });
    }

    return NextResponse.json({ success: true, data: stream });
  } catch (error) {
    console.error('Error fetching stream actions:', error);
    return NextResponse.json({ success: false, error: 'Failed to fetch stream actions' }, { status: 500 });
  }
}

// PUT /api/admin/streams/actions - Update stream action configuration
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = StreamActionSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }
    
    const { stream_id, ...actionData } = result.data;

    if (actionData.actionOptions) {
      try {
        JSON.parse(actionData.actionOptions);
      } catch (e) {
        return NextResponse.json({ success: false, error: 'actionOptions must be a valid JSON string' }, { status: 400 });
      }
    }
    
    const stream = await db.stream.update({
      where: { id: stream_id },
      data: actionData,
      select: { id: true, actionType: true, actionPayload: true, actionOptions: true }
    });

    return NextResponse.json({ success: true, data: stream });
  } catch (error: any) {
    console.error('Error updating stream action:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Stream not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to update stream action' }, { status: 500 });
  }
}
