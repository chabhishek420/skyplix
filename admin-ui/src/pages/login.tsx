import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api, setAuthToken } from '@/lib/api';
import { MousePointerClick, Loader2 } from 'lucide-react';

export function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const res = await api.post('/auth/login', { username, password });
      setAuthToken(res.data.token);
      navigate('/');
    } catch (err: any) {
      setError(err.response?.data?.error || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 flex items-center justify-center p-4 font-sans">
      <div className="max-w-md w-full space-y-8 bg-white p-10 rounded-2xl border border-slate-200 whisper-shadow animate-in fade-in zoom-in-95 duration-500">
        <div className="text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white whisper-shadow mb-6">
            <MousePointerClick className="w-8 h-8" />
          </div>
          <h2 className="text-3xl font-extrabold text-slate-900 tracking-tight">SkyPlix TDS</h2>
          <p className="mt-2 text-sm font-medium text-slate-500 uppercase tracking-widest">Admin Control Center</p>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleLogin}>
          {error && (
            <div className="bg-rose-50 border border-rose-100 text-rose-600 px-4 py-3 rounded-lg text-sm font-medium animate-in slide-in-from-top-2">
              {error}
            </div>
          )}

          <div className="space-y-4">
            <div>
              <label className="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Username</label>
              <input
                type="text"
                required
                className="mt-1 block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all"
                placeholder="admin"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
              />
            </div>
            <div>
              <label className="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Password</label>
              <input
                type="password"
                required
                className="mt-1 block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white transition-all"
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="group relative w-full flex justify-center py-4 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all whisper-shadow active:scale-[0.98] disabled:opacity-70"
          >
            {loading ? <Loader2 className="w-5 h-5 animate-spin" /> : "Sign in to Dashboard"}
          </button>
        </form>
      </div>
    </div>
  );
}
