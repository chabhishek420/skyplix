/**
 * Settings Admin API
 * CRUD operations for system settings
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/settings - List all settings
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const settings = await db.setting.findMany({
      orderBy: { key: 'asc' }
    });

    // Parse values based on type
    const parsedSettings = settings.map(s => {
      let value: string | number | boolean | Record<string, unknown> = s.value;
      switch (s.type) {
        case 'number':
          value = parseFloat(s.value);
          break;
        case 'boolean':
          value = s.value === 'true';
          break;
        case 'json':
          try {
            value = JSON.parse(s.value) as Record<string, unknown>;
          } catch {
            value = s.value;
          }
          break;
      }
      return {
        ...s,
        value
      };
    });

    return NextResponse.json({
      success: true,
      data: parsedSettings
    });
  } catch (error) {
    console.error('Error fetching settings:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to fetch settings' },
      { status: 500 }
    );
  }
}

// POST /api/admin/settings - Create new setting
export async function POST(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    
    // Convert value to string for storage
    let stringValue = body.value;
    if (typeof body.value !== 'string') {
      stringValue = JSON.stringify(body.value);
    }

    const setting = await db.setting.create({
      data: {
        key: body.key,
        value: stringValue,
        type: body.type || 'string',
        description: body.description
      }
    });

    return NextResponse.json({
      success: true,
      data: setting
    });
  } catch (error: unknown) {
    console.error('Error creating setting:', error);
    const prismaError = error as { code?: string };
    if (prismaError.code === 'P2002') {
      return NextResponse.json(
        { success: false, error: 'Setting key already exists' },
        { status: 400 }
      );
    }
    return NextResponse.json(
      { success: false, error: 'Failed to create setting' },
      { status: 500 }
    );
  }
}

// PUT /api/admin/settings - Update setting
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const body = await request.json();
    const { key, value, type, description } = body;

    if (!key) {
      return NextResponse.json(
        { success: false, error: 'Setting key is required' },
        { status: 400 }
      );
    }

    // Convert value to string for storage
    let stringValue = value;
    if (typeof value !== 'string') {
      stringValue = JSON.stringify(value);
    }

    const setting = await db.setting.update({
      where: { key },
      data: {
        value: stringValue,
        type: type || 'string',
        description
      }
    });

    return NextResponse.json({
      success: true,
      data: setting
    });
  } catch (error) {
    console.error('Error updating setting:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to update setting' },
      { status: 500 }
    );
  }
}

// DELETE /api/admin/settings - Delete setting
export async function DELETE(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const key = searchParams.get('key');

    if (!key) {
      return NextResponse.json(
        { success: false, error: 'Setting key is required' },
        { status: 400 }
      );
    }

    await db.setting.delete({
      where: { key }
    });

    return NextResponse.json({
      success: true,
      message: 'Setting deleted'
    });
  } catch (error) {
    console.error('Error deleting setting:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to delete setting' },
      { status: 500 }
    );
  }
}
