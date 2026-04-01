/**
 * Admin Authentication Middleware
 * 
 * Provides authentication for admin API endpoints.
 * Uses API key-based authentication with Bearer token.
 */

import { NextRequest, NextResponse } from 'next/server';
import crypto from 'crypto';

// Admin API key from environment or default for development
const ADMIN_API_KEY = process.env.ADMIN_API_KEY || 'tds-admin-secret-key-change-in-production';

/**
 * Get hash of the API key for session storage
 */
function getApiKeyHash(): string {
  return crypto.createHash('sha256').update(ADMIN_API_KEY).digest('hex');
}

/**
 * Authentication result
 */
export interface AuthResult {
  authenticated: boolean;
  error?: string;
  user?: {
    type: 'admin' | 'api';
    keyId?: string;
  };
}

/**
 * Verify admin authentication from request
 * Returns object with authenticated status
 */
export function verifyAdminAuth(request: NextRequest): AuthResult & { authenticated: boolean } {
  // Check for API key in header
  const authHeader = request.headers.get('authorization');
  
  if (authHeader) {
    // Bearer token format
    if (authHeader.startsWith('Bearer ')) {
      const token = authHeader.substring(7);
      if (token === ADMIN_API_KEY) {
        return {
          authenticated: true,
          user: { type: 'admin', keyId: 'bearer' }
        };
      }
    }
    
    // Direct API key
    if (authHeader === ADMIN_API_KEY) {
      return {
        authenticated: true,
        user: { type: 'api', keyId: 'direct' }
      };
    }
  }
  
  // Check for X-API-Key header
  const apiKeyHeader = request.headers.get('x-api-key');
  if (apiKeyHeader === ADMIN_API_KEY) {
    return {
      authenticated: true,
      user: { type: 'api', keyId: 'x-api-key' }
    };
  }
  
  // NOTE: Query parameter auth (?api_key=) removed for security (Phase 6)
  
  // Check for cookie-based session (for browser access)
  const sessionCookie = request.cookies.get('admin_session');
  if (sessionCookie && sessionCookie.value === getApiKeyHash()) {
    return {
      authenticated: true,
      user: { type: 'admin', keyId: 'cookie' }
    };
  }
  
  return {
    authenticated: false,
    error: 'Unauthorized - Invalid or missing API key'
  };
}

/**
 * Middleware wrapper for admin routes
 * Returns 401 if not authenticated
 */
export function withAdminAuth<T>(
  handler: (request: NextRequest, context: T) => Promise<NextResponse>
): (request: NextRequest, context: T) => Promise<NextResponse> {
  return async (request: NextRequest, context: T) => {
    const authResult = verifyAdminAuth(request);
    
    if (!authResult.authenticated) {
      return NextResponse.json(
        { 
        error: 'Unauthorized',
        message: authResult.error,
        hint: 'Provide API key via Authorization header or X-API-Key header'
      },
        { status: 401 }
      );
    }
    
    return handler(request, context);
  };
}

/**
 * Higher-order function to add auth to route handlers
 */
export function createAuthenticatedRoute<T>(
  handler: (request: NextRequest, context: T, auth: AuthResult) => Promise<NextResponse>
): (request: NextRequest, context: T) => Promise<NextResponse> {
  return async (request: NextRequest, context: T) => {
    const authResult = verifyAdminAuth(request);
    
    if (!authResult.authenticated) {
      return NextResponse.json(
        { 
          error: 'Unauthorized',
          message: authResult.error
        },
        { status: 401 }
      );
    }
    
    return handler(request, context, authResult);
  };
}

/**
 * Login endpoint helper - creates session cookie
 */
export function createAdminSession(apiKey: string): { success: boolean; cookie?: string; error?: string } {
  if (apiKey !== ADMIN_API_KEY) {
    return { success: false, error: 'Invalid API key' };
  }
  
  // Create session cookie that lasts 24 hours (using hash, not raw key)
  const hashedValue = getApiKeyHash();
  const cookieValue = `admin_session=${hashedValue}; Path=/; HttpOnly; SameSite=Strict; Max-Age=${60 * 60 * 24}`;
  
  return {
    success: true,
    cookie: cookieValue
  };
}

/**
 * Logout - clear session cookie
 */
export function clearAdminSession(): string {
  return 'admin_session=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0';
}

/**
 * Create session cookie value
 */
export function createSessionCookie(): string {
  return `admin_session=${getApiKeyHash()}; Path=/; HttpOnly; SameSite=Strict; Max-Age=${60 * 60 * 24}`;
}

/**
 * Get the current API key (for display in settings)
 */
export function getApiKeyPreview(): string {
  if (ADMIN_API_KEY.length > 8) {
    return ADMIN_API_KEY.substring(0, 4) + '****' + ADMIN_API_KEY.substring(ADMIN_API_KEY.length - 4);
  }
  return '****';
}

/**
 * Check if request is from local development
 */
export function isLocalDevelopment(request: NextRequest): boolean {
  const host = request.headers.get('host') || '';
  return host.includes('localhost') || host.includes('127.0.0.1');
}

/**
 * Check auth and return unauthorized response if failed
 * Use this in admin route handlers
 */
export function checkAuth(request: NextRequest): NextResponse | null {
  // In development mode, skip auth on localhost for convenience
  // WARNING: This should be disabled in production!
  const skipAuth = process.env.NODE_ENV !== 'production' && isLocalDevelopment(request);
  
  if (!skipAuth) {
    const authResult = verifyAdminAuth(request);
    if (!authResult.authenticated) {
      return NextResponse.json(
        {
          error: 'Unauthorized',
          message: authResult.error,
          hint: 'Authenticate via POST /api/admin/login or include X-API-Key header'
        },
        { status: 401 }
      );
    }
  }
  
  return null; // Auth passed, continue with request
}
