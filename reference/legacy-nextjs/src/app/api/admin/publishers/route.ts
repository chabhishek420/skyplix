/**
 * Publisher Management API
 * 
 * CRUD operations for publishers
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/publishers - List all publishers
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const status = searchParams.get('status');
    const search = searchParams.get('search');
    
    const where: Record<string, unknown> = {};
    
    if (status) {
      where.status = status;
    }
    
    if (search) {
      where.OR = [
        { name: { contains: search } },
        { pubId: { equals: parseInt(search) } },
        { email: { contains: search } }
      ];
    }
    
    const publishers = await db.publisher.findMany({
      where,
      include: {
        campaignAccess: {
          include: { campaign: true }
        },
        _count: {
          select: { clicks: true }
        }
      },
      orderBy: { createdAt: 'desc' }
    });
    
    return NextResponse.json({ publishers });
    
  } catch (error) {
    console.error('Error fetching publishers:', error);
    return NextResponse.json({ error: 'Failed to fetch publishers' }, { status: 500 });
  }
}

// POST /api/admin/publishers - Create new publisher
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    
    // Generate pub ID if not provided
    const pubId = body.pubId || await generatePublisherId();
    
    const publisher = await db.publisher.create({
      data: {
        pubId,
        name: body.name,
        email: body.email,
        status: body.status || 'active',
        source: body.source,
        referrer: body.referrer
      }
    });
    
    return NextResponse.json({ publisher });
    
  } catch (error) {
    console.error('Error creating publisher:', error);
    return NextResponse.json({ error: 'Failed to create publisher' }, { status: 500 });
  }
}

// PUT /api/admin/publishers - Update publisher
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    const { id, ...data } = body;
    
    if (!id) {
      return NextResponse.json({ error: 'Publisher ID required' }, { status: 400 });
    }
    
    const publisher = await db.publisher.update({
      where: { id },
      data: {
        name: data.name,
        email: data.email,
        status: data.status,
        source: data.source,
        referrer: data.referrer
      }
    });
    
    return NextResponse.json({ publisher });
    
  } catch (error) {
    console.error('Error updating publisher:', error);
    return NextResponse.json({ error: 'Failed to update publisher' }, { status: 500 });
  }
}

// DELETE /api/admin/publishers - Delete publisher
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ error: 'Publisher ID required' }, { status: 400 });
    }
    
    // Soft delete
    const publisher = await db.publisher.update({
      where: { id },
      data: { status: 'deleted' }
    });
    
    return NextResponse.json({ success: true, publisher });
    
  } catch (error) {
    console.error('Error deleting publisher:', error);
    return NextResponse.json({ error: 'Failed to delete publisher' }, { status: 500 });
  }
}

/**
 * Generate next publisher ID
 */
async function generatePublisherId(): Promise<number> {
  const lastPublisher = await db.publisher.findFirst({
    orderBy: { pubId: 'desc' }
  });
  
  return (lastPublisher?.pubId || 102200) + 1;
}
