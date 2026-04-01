/**
 * Bot Rules Admin API
 * CRUD operations for bot detection rules
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/bot-rules - List all bot rules
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

    const rules = await db.botRule.findMany({
      where,
      orderBy: [
        { priority: 'desc' },
        { createdAt: 'desc' }
      ]
    });

    return NextResponse.json({
      success: true,
      data: rules
    });
  } catch (error) {
    console.error('Error fetching bot rules:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch bot rules' },
      { status: 500 }
    );
  }
}

// POST /api/admin/bot-rules - Create new bot rule
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    
    const rule = await db.botRule.create({
      data: {
        name: body.name,
        type: body.type, // ip, user_agent, referrer, parameter, header
        pattern: body.pattern,
        matchType: body.matchType || 'regex', // regex, exact, contains
        action: body.action || 'safe_page', // safe_page, block, redirect
        redirectUrl: body.redirectUrl,
        priority: body.priority || 100,
        status: body.status || 'active'
      }
    });

    return NextResponse.json({
      success: true,
      data: rule
    });
  } catch (error) {
    console.error('Error creating bot rule:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to create bot rule' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/bot-rules - Update bot rule
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    const { id, ...data } = body;

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Rule ID is required' },
        { status: 400 }
      );
    }

    const rule = await db.botRule.update({
      where: { id },
      data: {
        name: data.name,
        type: data.type,
        pattern: data.pattern,
        matchType: data.matchType,
        action: data.action,
        redirectUrl: data.redirectUrl,
        priority: data.priority,
        status: data.status
      }
    });

    return NextResponse.json({
      success: true,
      data: rule
    });
  } catch (error) {
    console.error('Error updating bot rule:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update bot rule' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/bot-rules - Delete bot rule
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');

    if (!id) {
      return NextResponse.json(
        { success: false, error: 'Rule ID is required' },
        { status: 400 }
      );
    }

    await db.botRule.delete({
      where: { id }
    });

    return NextResponse.json({
      success: true,
      message: 'Bot rule deleted'
    });
  } catch (error) {
    console.error('Error deleting bot rule:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete bot rule' },
      { status: 500 }
    );
  }
}
