/**
 * Authentication Module
 * 
 * Provides admin authentication for API endpoints.
 * 
 * Usage:
 * 1. Set ADMIN_API_KEY in environment (or use default for development)
 * 2. Call verifyAdminAuth(request) in protected routes
 * 3. Or use withAdminAuth wrapper for automatic protection
 * 
 * Authentication methods (in order of security):
 * - Bearer token in Authorization header
 * - X-API-Key header
 * - Cookie-based session (for browser access)
 * - Query parameter (for testing only - NOT recommended for production)
 */

export {
  verifyAdminAuth,
  withAdminAuth,
  createAuthenticatedRoute,
  createAdminSession,
  clearAdminSession,
  createSessionCookie,
  getApiKeyPreview,
  isLocalDevelopment,
  checkAuth,
  type AuthResult
} from './admin-auth';
