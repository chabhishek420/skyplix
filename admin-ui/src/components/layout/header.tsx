import { Bell, LogOut, User } from 'lucide-react';

export function Header() {
  const handleLogout = () => {
    localStorage.removeItem('api_key');
    window.location.href = '/admin/';
  };

  return (
    <header className="h-16 w-[calc(100%-16rem)] fixed top-0 right-0 border-b border-[#e2e8f0] bg-white z-10 flex items-center justify-between px-8">
      <div className="flex items-center space-x-2 text-[13px]">
        <span className="text-slate-400">Admin</span>
        <span className="text-slate-300 scale-75">/</span>
        <span className="text-slate-900 font-bold tracking-tight">Dashboard</span>
      </div>

      <div className="flex items-center space-x-6">
        <button className="p-1 text-slate-400 hover:text-blue-600 transition-colors relative">
          <Bell className="w-5 h-5" />
          <span className="absolute -top-0.5 -right-0.5 w-2 h-2 bg-rose-500 rounded-full border-2 border-white"></span>
        </button>

        <div className="flex items-center space-x-3 cursor-pointer group">
          <div className="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 overflow-hidden border border-slate-200 transition-all group-hover:border-blue-200">
            <User className="w-4 h-4" />
          </div>
          <button onClick={handleLogout} className="p-1 text-slate-400 hover:text-rose-500 transition-colors" title="Logout">
            <LogOut className="w-4 h-4" />
          </button>
        </div>
      </div>
    </header>
  );
}
