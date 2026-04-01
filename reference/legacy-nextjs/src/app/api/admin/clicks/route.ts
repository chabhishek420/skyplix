/**
 * Clicks Log API
 * 
 * Browse and search click records
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/clicks - List clicks
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    
    // Pagination
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '50');
    const offset = (page - 1) * limit;
    
    // Filters
    const campaignId = searchParams.get('campaignId');
    const publisherId = searchParams.get('publisherId');
    const isBot = searchParams.get('isBot');
    const search = searchParams.get('search');
    
    const where: Record<string, unknown> = {};
    
    if (campaignId) {
      where.campaignId = campaignId;
    }
    
    if (publisherId) {
      where.publisherId = publisherId;
    }
    
    if (isBot !== null) {
      where.isBot = isBot === 'true';
    }
    
    if (search) {
      where.OR = [
        { clickId: { contains: search } },
        { ip: { contains: search } },
        { userAgent: { contains: search } }
      ];
    }
    
    // Get total count
    const total = await db.click.count({ where });
    
    // Get clicks
    const clicks = await db.click.findMany({
      where,
      include: {
        campaign: {
          select: { id: true, name: true, campaignId: true }
        },
        publisher: {
          select: { id: true, name: true, pubId: true }
        },
        stream: {
          select: { id: true, name: true }
        },
        conversion: {
          select: { 
            id: true, 
            status: true, 
            payout: true, 
            postbackAt: true 
          }
        }
      },
      orderBy: { clickedAt: 'desc' },
      skip: offset,
      take: limit
    });
    
    return NextResponse.json({
      clicks,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit)
      }
    });
    
  } catch (error) {
    console.error('Error fetching clicks:', error);
    return NextResponse.json({ error: 'Failed to fetch clicks' }, { status: 500 });
  }
}

// DELETE /api/admin/clicks - Delete click record
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ error: 'Click ID required' }, { status: 400 });
    }
    
    await db.click.delete({
      where: { id }
    });
    
    return NextResponse.json({ success: true });
    
  } catch (error) {
    console.error('Error deleting click:', error);
    return NextResponse.json({ error: 'Failed to delete click' }, { status: 500 });
  }
}
