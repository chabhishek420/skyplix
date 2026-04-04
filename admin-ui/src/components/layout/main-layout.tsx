import { Outlet } from 'react-router-dom';
import { Sidebar } from './sidebar';
import { Header } from './header';

export function MainLayout() {
  return (
    <div className="flex min-h-screen bg-background text-foreground font-sans">
      <Sidebar />
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <Header />
        <main className="flex-1 overflow-y-auto scroll-smooth">
          <div className="p-8 max-w-7xl mx-auto w-full animate-in fade-in slide-in-from-bottom-4 duration-500 fill-mode-both">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
