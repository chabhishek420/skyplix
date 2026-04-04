import { Bell, LogOut, User } from 'lucide-react';

export function Header() {
  const handleLogout = () => {
    localStorage.removeItem('api_key');
    window.location.href = '/admin/';
  };

  return (
    <header className="h-16 border-b border-border bg-card sticky top-0 z-10 flex items-center justify-between px-6">
      <div className="flex items-center space-x-4">
        {/* Breadcrumb or title context could go here */}
      </div>

      <div className="flex items-center space-x-4">
        <button className="p-2 text-muted-foreground hover:bg-muted hover:text-foreground rounded-full transition-colors relative">
          <Bell className="w-5 h-5" />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-primary rounded-full ring-2 ring-card"></span>
        </button>

        <div className="h-8 w-px bg-border mx-2"></div>

        <div className="flex items-center space-x-3 cursor-pointer group">
          <div className="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-all">
            <User className="w-5 h-5" />
          </div>
          <button onClick={handleLogout} className="p-2 text-muted-foreground hover:text-destructive transition-colors" title="Logout">
            <LogOut className="w-5 h-5" />
          </button>
        </div>
      </div>
    </header>
  );
}
