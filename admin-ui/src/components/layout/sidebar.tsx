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
  Settings,
  ShieldCheck
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
    ]
  },
  {
    label: 'Analysis',
    items: [
      { path: '/logs/clicks', label: 'Clicks Log', icon: MousePointerClick },
      { path: '/logs/conversions', label: 'Conversions', icon: MonitorOff },
    ]
  }
];

export function Sidebar() {
  return (
    <aside className="w-64 border-r border-border bg-card flex flex-col transition-all h-screen sticky top-0 overflow-hidden select-none">
      <div className="p-8 flex items-center space-x-3 mb-4">
        <div className="w-10 h-10 rounded-lg bg-primary flex items-center justify-center shadow-sm">
          <ShieldCheck className="w-6 h-6 text-primary-foreground" />
        </div>
        <div>
          <span className="font-extrabold text-xl tracking-tight text-foreground block leading-none">SkyPlix</span>
          <span className="text-[10px] uppercase tracking-widest font-bold text-primary/80">TDS Core</span>
        </div>
      </div>

      <nav className="flex-1 px-4 space-y-8 overflow-y-auto pb-8 scrollbar-hide">
        {MENU_GROUPS.map((group) => (
          <div key={group.label}>
            <h3 className="px-4 mb-3 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">{group.label}</h3>
            <div className="space-y-1">
              {group.items.map((item) => (
                <NavLink
                  key={item.path}
                  to={item.path}
                  className={({ isActive }) =>
                    `flex items-center space-x-3 px-4 py-2 rounded-md text-[13px] font-medium transition-all group ${
                      isActive
                        ? 'bg-primary/10 text-primary font-semibold border-l-2 border-primary'
                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground border-l-2 border-transparent'
                    }`
                  }
                >
                  {({ isActive }) => (
                    <>
                      <item.icon className={`w-4.5 h-4.5 transition-transform group-hover:scale-110 group-active:scale-95 duration-200 ${isActive ? 'text-primary' : 'opacity-70'}`} />
                      <span>{item.label}</span>
                    </>
                  )}
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>
      
      <div className="p-6 border-t border-border bg-muted/20 flex flex-col items-center gap-2">
        <div className="flex items-center gap-2 text-[12px] font-medium text-muted-foreground">
          <Settings className="w-3.5 h-3.5" />
          <span>System Settings</span>
        </div>
        <div className="text-[10px] text-muted-foreground/50 font-mono">v1.2.0-STABLE</div>
      </div>
    </aside>
  );
}
