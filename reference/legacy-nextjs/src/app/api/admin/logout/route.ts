/**
 * Admin Logout API
 * 
 * Clears admin session cookie.
 */

import { NextResponse } from 'next/server';
import { clearAdminSession } from '@/lib/auth';

export async function POST() {
  const response = NextResponse.json({ 
    success: true, 
    message: 'Logged out successfully' 
  });
  
  response.headers.set('Set-Cookie', clearAdminSession());
  
  return response;
}

export async function GET() {
  // Also support GET for easy browser logout
  const response = NextResponse.redirect(new URL('/', process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000'));
  response.headers.set('Set-Cookie', clearAdminSession());
  return response;
}
