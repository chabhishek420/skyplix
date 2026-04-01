import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const GroupSchema = z.object({
  name: z.string().min(1, "Name is required"),
  type: z.string().default("campaigns"),
  position: z.number().int().default(0)
});

// GET /api/admin/groups - List all groups
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const type = searchParams.get('type');
    
    const where: Record<string, unknown> = {};
    if (type) {
      where.type = type;
    }
    
    const groups = await db.group.findMany({
      where,
      orderBy: [{ position: 'asc' }, { name: 'asc' }]
    });

    return NextResponse.json({ success: true, data: groups });
  } catch (error) {
    console.error('Error fetching groups:', error);
    return NextResponse.json({ success: false, error: 'Failed to fetch groups' }, { status: 500 });
  }
}

// POST /api/admin/groups - Create a group
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = GroupSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }
    
    const group = await db.group.create({
      data: result.data
    });

    return NextResponse.json({ success: true, data: group });
  } catch (error: any) {
    console.error('Error creating group:', error);
    if (error.code === 'P2002') {
      return NextResponse.json({ success: false, error: 'A group with this name and type already exists' }, { status: 400 });
    }
    return NextResponse.json({ success: false, error: 'Failed to create group' }, { status: 500 });
  }
}

// PUT /api/admin/groups - Update a group
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Group ID is required' }, { status: 400 });
    }
    
    const json = await request.json();
    const result = GroupSchema.partial().safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }
    
    const group = await db.group.update({
      where: { id },
      data: result.data
    });

    return NextResponse.json({ success: true, data: group });
  } catch (error: any) {
    console.error('Error updating group:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Group not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to update group' }, { status: 500 });
  }
}

// DELETE /api/admin/groups - Delete a group
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Group ID is required' }, { status: 400 });
    }
    
    await db.group.delete({
      where: { id }
    });

    return NextResponse.json({ success: true, message: 'Group deleted successfully' });
  } catch (error: any) {
    console.error('Error deleting group:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Group not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to delete group' }, { status: 500 });
  }
}
