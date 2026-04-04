import { Outlet } from 'react-router-dom';
import { Sidebar } from './sidebar';
import { Header } from './header';

export function MainLayout() {
  return (
    <div className="flex min-h-screen bg-[#f8fafc] text-slate-900 font-sans">
      <Sidebar />
      <div className="flex-1 ml-64 flex flex-col min-w-0">
        <Header />
        <main className="flex-1 pt-16 h-screen overflow-y-auto scroll-smooth group">
          <div className="p-8 max-w-[1600px] w-full animate-in fade-in transition-all duration-700">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
