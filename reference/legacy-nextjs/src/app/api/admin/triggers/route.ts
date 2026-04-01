import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const TriggerSchema = z.object({
  name: z.string().min(1, "Name is required"),
  targetType: z.enum(['campaign', 'stream']),
  targetId: z.string().optional().nullable(),
  condition: z.string().min(2, "Condition must be valid JSON object string"),
  action: z.string().min(1, "Action is required"),
  status: z.enum(['active', 'paused']).default('active')
});

// GET /api/admin/triggers - List all triggers
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const targetType = searchParams.get('targetType');
    const targetId = searchParams.get('targetId');
    
    const where: Record<string, unknown> = {};
    if (targetType) where.targetType = targetType;
    if (targetId) where.targetId = targetId;
    
    const triggers = await db.trigger.findMany({
      where,
      orderBy: { createdAt: 'desc' }
    });

    return NextResponse.json({ success: true, data: triggers });
  } catch (error) {
    console.error('Error fetching triggers:', error);
    return NextResponse.json({ success: false, error: 'Failed to fetch triggers' }, { status: 500 });
  }
}

// POST /api/admin/triggers - Create a trigger
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = TriggerSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }
    
    // Ensure condition is valid JSON
    try {
      JSON.parse(result.data.condition);
    } catch (e) {
      return NextResponse.json({ success: false, error: 'Condition must be a valid JSON string' }, { status: 400 });
    }

    const trigger = await db.trigger.create({
      data: result.data
    });

    return NextResponse.json({ success: true, data: trigger });
  } catch (error: any) {
    console.error('Error creating trigger:', error);
    return NextResponse.json({ success: false, error: 'Failed to create trigger' }, { status: 500 });
  }
}

// PUT /api/admin/triggers - Update a trigger
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Trigger ID is required' }, { status: 400 });
    }
    
    const json = await request.json();
    const result = TriggerSchema.partial().safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }

    if (result.data.condition) {
      try {
        JSON.parse(result.data.condition);
      } catch (e) {
        return NextResponse.json({ success: false, error: 'Condition must be a valid JSON string' }, { status: 400 });
      }
    }
    
    const trigger = await db.trigger.update({
      where: { id },
      data: result.data
    });

    return NextResponse.json({ success: true, data: trigger });
  } catch (error: any) {
    console.error('Error updating trigger:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Trigger not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to update trigger' }, { status: 500 });
  }
}

// DELETE /api/admin/triggers - Delete a trigger
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Trigger ID is required' }, { status: 400 });
    }
    
    await db.trigger.delete({
      where: { id }
    });

    return NextResponse.json({ success: true, message: 'Trigger deleted successfully' });
  } catch (error: any) {
    console.error('Error deleting trigger:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Trigger not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to delete trigger' }, { status: 500 });
  }
}
