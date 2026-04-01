/**
 * Admin Login API
 * 
 * Authenticates admin and creates session cookie.
 * POST /api/admin/login
 * 
 * Body: { api_key: string }
 */

import { NextRequest, NextResponse } from 'next/server';
import { createAdminSession, verifyAdminAuth } from '@/lib/auth';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { api_key } = body;
    
    if (!api_key) {
      return NextResponse.json(
        { error: 'API key required' },
        { status: 400 }
      );
    }
    
    const result = createAdminSession(api_key);
    
    if (!result.success) {
      return NextResponse.json(
        { error: result.error || 'Invalid API key' },
        { status: 401 }
      );
    }
    
    const response = NextResponse.json({ 
      success: true, 
      message: 'Logged in successfully' 
    });
    
    if (result.cookie) {
      response.headers.set('Set-Cookie', result.cookie);
    }
    
    return response;
  } catch (error) {
    return NextResponse.json(
      { error: 'Invalid request body' },
      { status: 400 }
    );
  }
}

export async function GET(request: NextRequest) {
  // Check if already logged in
  const authResult = verifyAdminAuth(request);
  
  if (authResult.authenticated) {
    return NextResponse.json({ 
      authenticated: true,
      user: authResult.user 
    });
  }
  
  return NextResponse.json({ 
    authenticated: false,
    hint: 'POST to /api/admin/login with { api_key: "your-key" } to authenticate'
  });
}
