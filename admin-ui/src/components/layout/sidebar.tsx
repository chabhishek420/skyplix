import { NavLink } from 'react-router-dom';
import { 
  LayoutDashboard, 
  Megaphone, 
  Target, 
  MousePointerClick, 
  Network, 
  Activity, 
  MonitorOff, 
  FileBox, 
  Globe,
  ShieldCheck,
  BarChart2,
  Users
} from 'lucide-react';

const MENU_GROUPS = [
  {
    label: 'Main',
    items: [
      { path: '/', label: 'Overview', icon: LayoutDashboard },
      { path: '/campaigns', label: 'Campaigns', icon: Megaphone },
    ]
  },
  {
    label: 'Landers & Offers',
    items: [
      { path: '/offers', label: 'Offers', icon: Target },
      { path: '/landings', label: 'Landings', icon: FileBox },
      { path: '/networks', label: 'Networks', icon: Network },
    ]
  },
  {
    label: 'Settings',
    items: [
      { path: '/sources', label: 'Sources', icon: Activity },
      { path: '/domains', label: 'Domains', icon: Globe },
      { path: '/users', label: 'Users', icon: Users },
    ]
  },
  {
    label: 'Analysis',
    items: [
      { path: '/stats', label: 'Drilldowns', icon: BarChart2 },
      { path: '/logs/clicks', label: 'Clicks Log', icon: MousePointerClick },
      { path: '/logs/conversions', label: 'Conversions', icon: MonitorOff },
    ]
  }
];

export function Sidebar() {
  return (
    <aside className="w-64 border-r border-[#e2e8f0] bg-white flex flex-col transition-all h-screen fixed left-0 top-0 z-20 overflow-hidden select-none">
      <div className="p-6 flex items-center space-x-3 mb-4">
        <div className="w-9 h-9 rounded bg-[#2563eb] flex items-center justify-center shadow-sm">
          <ShieldCheck className="w-5 h-5 text-white" />
        </div>
        <div>
          <span className="font-extrabold text-xl tracking-tight text-slate-900 block leading-none">SkyPlix</span>
          <span className="text-[10px] uppercase tracking-[0.2em] font-bold text-slate-400 mt-1 block">TDS CORE</span>
        </div>
      </div>

      <nav className="flex-1 space-y-6 overflow-y-auto pb-8 scrollbar-hide">
        {MENU_GROUPS.map((group) => (
          <div key={group.label}>
            <h3 className="px-6 mb-2 text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400">{group.label}</h3>
            <div className="space-y-0.5">
              {group.items.map((item) => (
                <NavLink
                  key={item.path}
                  to={item.path}
                  className={({ isActive }) =>
                    `flex items-center space-x-3 px-6 py-2.5 text-[13px] font-medium transition-all group ${
                      isActive
                        ? 'bg-[#f8fafc] text-[#2563eb] font-semibold border-l-2 border-[#2563eb]'
                        : 'text-slate-500 hover:bg-[#f8fafc] hover:text-slate-900 border-l-2 border-transparent'
                    }`
                  }
                >
                  {({ isActive }) => (
                    <>
                      <item.icon className={`w-4 h-4 transition-transform group-hover:scale-110 duration-200 ${isActive ? 'text-[#2563eb]' : 'text-slate-400'}`} />
                      <span className="font-inter">{item.label}</span>
                    </>
                  )}
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>
      
      <div className="p-6 border-t border-slate-100 bg-white">
        <div className="flex items-center gap-2 text-[10px] font-mono font-medium text-slate-400">
          <div className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
          <span>v1.2.0-STABLE</span>
        </div>
      </div>
    </aside>
  );
}
