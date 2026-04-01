import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const LabelSchema = z.object({
  name: z.string().min(1, "Name is required"),
  color: z.string().regex(/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/, "Must be a valid hex color").default("#000000")
});

// GET /api/admin/labels - List all labels
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const labels = await db.label.findMany({
      orderBy: { name: 'asc' }
    });

    return NextResponse.json({ success: true, data: labels });
  } catch (error) {
    console.error('Error fetching labels:', error);
    return NextResponse.json({ success: false, error: 'Failed to fetch labels' }, { status: 500 });
  }
}

// POST /api/admin/labels - Create a label
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = LabelSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }
    
    const label = await db.label.create({
      data: result.data
    });

    return NextResponse.json({ success: true, data: label });
  } catch (error: any) {
    console.error('Error creating label:', error);
    if (error.code === 'P2002') {
      return NextResponse.json({ success: false, error: 'A label with this name already exists' }, { status: 400 });
    }
    return NextResponse.json({ success: false, error: 'Failed to create label' }, { status: 500 });
  }
}

// PUT /api/admin/labels - Update a label
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Label ID is required' }, { status: 400 });
    }
    
    const json = await request.json();
    const result = LabelSchema.partial().safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }
    
    const label = await db.label.update({
      where: { id },
      data: result.data
    });

    return NextResponse.json({ success: true, data: label });
  } catch (error: any) {
    console.error('Error updating label:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Label not found' }, { status: 404 });
    }
    if (error.code === 'P2002') {
      return NextResponse.json({ success: false, error: 'A label with this name already exists' }, { status: 400 });
    }
    return NextResponse.json({ success: false, error: 'Failed to update label' }, { status: 500 });
  }
}

// DELETE /api/admin/labels - Delete a label
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ success: false, error: 'Label ID is required' }, { status: 400 });
    }
    
    await db.label.delete({
      where: { id }
    });

    return NextResponse.json({ success: true, message: 'Label deleted successfully' });
  } catch (error: any) {
    console.error('Error deleting label:', error);
    if (error.code === 'P2025') {
      return NextResponse.json({ success: false, error: 'Label not found' }, { status: 404 });
    }
    return NextResponse.json({ success: false, error: 'Failed to delete label' }, { status: 500 });
  }
}
