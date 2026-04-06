import React, { useState, useEffect } from 'react';
import { KeyRound, ShieldAlert } from 'lucide-react';
import { api } from '@/lib/api';

export function LoginGuard({ children }: { children: React.ReactNode }) {
  const [loading, setLoading] = useState(true);
  const [token, setToken] = useState(localStorage.getItem('auth_token') || '');
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [inputToken, setInputToken] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    const handleUnauthorized = () => {
      setIsAuthenticated(false);
      setToken('');
    };

    window.addEventListener('auth-unauthorized', handleUnauthorized);
    return () => window.removeEventListener('auth-unauthorized', handleUnauthorized);
  }, []);

  useEffect(() => {
    async function checkAuth() {
      if (!token) {
        setIsAuthenticated(false);
        setLoading(false);
        return;
      }
      try {
        await api.get('/settings');
        setIsAuthenticated(true);
        setError('');
      } catch (err) {
        setIsAuthenticated(false);
        localStorage.removeItem('auth_token');
      } finally {
        setLoading(false);
      }
    }
    checkAuth();
  }, [token]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!inputToken.trim()) return;
    
    setLoading(true);
    setError('');
    
    try {
      const resp = await api.post('/auth/login', { api_key: inputToken.trim() });
      const jwt = resp.data?.token;
      if (!jwt || typeof jwt !== 'string') {
        throw new Error('missing token');
      }

      localStorage.setItem('auth_token', jwt);
      setToken(jwt);
      setIsAuthenticated(true);
    } catch (err) {
      setError('Invalid API Key provided.');
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex h-screen items-center justify-center bg-background">
        <div className="animate-pulse flex flex-col items-center gap-4">
          <div className="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
          <p className="text-muted-foreground text-sm font-medium tracking-wider uppercase">Authenticating...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background text-foreground relative overflow-hidden">
        {/* Abstract background blur elements for aesthetics */}
        <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-primary/20 rounded-full mix-blend-screen filter blur-[100px] opacity-50 animate-pulse"></div>
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-accent/20 rounded-full mix-blend-screen filter blur-[100px] opacity-50 animate-pulse" style={{ animationDelay: '2s' }}></div>
        
        <div className="relative z-10 w-full max-w-sm rounded-2xl border border-border/50 bg-card/60 backdrop-blur-xl p-8 shadow-2xl">
          <div className="flex flex-col items-center space-y-4 mb-8">
            <div className="p-3 bg-primary/10 rounded-xl ring-1 ring-primary/20">
              <KeyRound className="w-8 h-8 text-primary" />
            </div>
            <h1 className="text-2xl font-bold tracking-tight text-card-foreground">Access Restricted</h1>
            <p className="text-sm text-muted-foreground text-center">
              Please enter your Secret API Key to access the SkyPlix Dashboard.
            </p>
          </div>

          <form onSubmit={handleLogin} className="space-y-6">
            <div className="space-y-2">
              <input 
                type="password"
                placeholder="X-Api-Key Token..."
                value={inputToken}
                onChange={(e) => setInputToken(e.target.value)}
                className="w-full px-4 py-3 rounded-lg border border-border bg-background/50 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all placeholder:text-muted-foreground"
              />
              {error && (
                <div className="flex items-center gap-2 text-destructive text-sm mt-2 font-medium">
                  <ShieldAlert className="w-4 h-4" />
                  {error}
                </div>
              )}
            </div>
            <button 
              type="submit"
              disabled={!inputToken.trim()}
              className="w-full py-3 rounded-lg bg-primary text-primary-foreground font-semibold tracking-wide hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Authenticate
            </button>
          </form>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}
