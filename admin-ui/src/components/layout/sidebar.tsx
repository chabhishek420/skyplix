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
  Globe
} from 'lucide-react';

const MENU_ITEMS = [
  { path: '/', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/campaigns', label: 'Campaigns', icon: Megaphone },
  { path: '/offers', label: 'Offers', icon: Target },
  { path: '/landings', label: 'Landings', icon: FileBox },
  { path: '/networks', label: 'Networks', icon: Network },
  { path: '/sources', label: 'Traffic Sources', icon: Activity },
  { path: '/domains', label: 'Domains', icon: Globe },
  { path: '/logs/clicks', label: 'Clicks Log', icon: MousePointerClick },
  { path: '/logs/conversions', label: 'Postbacks', icon: MonitorOff },
];

export function Sidebar() {
  return (
    <aside className="w-64 border-r border-border bg-card/50 backdrop-blur-md flex flex-col transition-all h-screen sticky top-0">
      <div className="p-6 flex items-center space-x-3">
        <div className="w-8 h-8 rounded-lg bg-primary flex items-center justify-center font-bold text-primary-foreground">
          S
        </div>
        <span className="font-bold text-xl tracking-tight text-foreground">SkyPlix TDS</span>
      </div>

      <nav className="flex-1 px-4 space-y-1 mt-4 overflow-y-auto">
        {MENU_ITEMS.map((item) => (
          <NavLink
            key={item.path}
            to={item.path}
            className={({ isActive }) =>
              `flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all group ${
                isActive
                  ? 'bg-primary/10 text-primary shadow-sm'
                  : 'text-muted-foreground hover:bg-muted hover:text-foreground'
              }`
            }
          >
            {({ isActive }) => (
              <>
                <item.icon className={`w-5 h-5 transition-transform group-hover:scale-110 ${isActive ? 'text-primary' : ''}`} />
                <span>{item.label}</span>
              </>
            )}
          </NavLink>
        ))}
      </nav>
      
      <div className="p-4 border-t border-border bg-card/30">
        <div className="text-xs text-center text-muted-foreground">Version 1.0.0</div>
      </div>
    </aside>
  );
}
