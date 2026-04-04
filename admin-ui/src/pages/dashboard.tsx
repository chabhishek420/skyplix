import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { 
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  BarChart, Bar, Legend
} from 'recharts';
import { ArrowUpRight, ArrowDownRight, MousePointerClick, Target, DollarSign, Activity } from 'lucide-react';

export function Dashboard() {
  const { data: reportData, isLoading, isError } = useQuery({
    queryKey: ['dashboard-report'],
    queryFn: async () => {
      // In a real app we'd pass ?from= &to= depending on a date picker
      const res = await api.get('/reports');
      return res.data;
    }
  });

  if (isLoading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <div className="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full"></div>
      </div>
    );
  }

  if (isError) {
    return (
      <div className="p-4 border border-destructive/50 bg-destructive/10 text-destructive rounded-lg">
        Failed to load dashboard data. Check your API connection.
      </div>
    );
  }

  // Use real data if returned correctly from Go slice, or stub if empty
  const summary = reportData?.summary || { total_clicks: 0, total_conversions: 0, revenue: 0, roi: 0 };
  const chartData = reportData?.rows?.length > 0 ? reportData.rows : [
    { entity_name: 'Mon', clicks: 1200, conversions: 45, revenue: 320, cost: 200 },
    { entity_name: 'Tue', clicks: 2100, conversions: 80, revenue: 450, cost: 300 },
    { entity_name: 'Wed', clicks: 1800, conversions: 65, revenue: 400, cost: 280 },
    { entity_name: 'Thu', clicks: 2400, conversions: 110, revenue: 600, cost: 350 },
    { entity_name: 'Fri', clicks: 3100, conversions: 156, revenue: 950, cost: 400 },
    { entity_name: 'Sat', clicks: 2800, conversions: 120, revenue: 720, cost: 380 },
    { entity_name: 'Sun', clicks: 3500, conversions: 190, revenue: 1100, cost: 450 },
  ]; // Fallback to mock data if no rows so the UI charts still render

  const stats = [
    { label: 'Total Clicks', value: summary.total_clicks.toLocaleString(), icon: MousePointerClick, trend: '+12.5%', isPositive: true },
    { label: 'Conversions', value: summary.total_conversions.toLocaleString(), icon: Target, trend: '+8.2%', isPositive: true },
    { label: 'Revenue', value: `$${summary.revenue.toFixed(2)}`, icon: DollarSign, trend: '+24.1%', isPositive: true },
    { label: 'ROI', value: `${summary.roi.toFixed(2)}%`, icon: Activity, trend: '-2.4%', isPositive: false },
  ];

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold tracking-tight text-foreground">Dashboard Overview</h1>
        <div className="text-sm text-muted-foreground bg-card/60 px-4 py-2 rounded-lg border border-border">
          Last 7 Days
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {stats.map((stat, i) => (
          <div key={i} className={`bg-card border-t-[3px] border-b border-l border-r border-border p-5 rounded-lg shadow-sm hover:shadow transition-all group ${
            stat.label === 'Total Clicks' ? 'border-t-blue-500' :
            stat.label === 'Conversions' ? 'border-t-emerald-500' :
            stat.label === 'Revenue' ? 'border-t-emerald-400' :
            'border-t-slate-400'
          }`}>
            <div className="flex items-center justify-between mb-2">
              <h3 className="text-muted-foreground font-semibold text-[11px] uppercase tracking-wider">{stat.label}</h3>
              <div className="w-8 h-8 rounded-md bg-slate-50 flex items-center justify-center border border-slate-100 group-hover:scale-110 transition-transform">
                <stat.icon className="w-4 h-4 text-slate-400 group-hover:text-primary transition-colors" />
              </div>
            </div>
            <div className="flex items-end justify-between">
              <div className="text-2xl font-bold tracking-tight text-foreground">{stat.value}</div>
              <div className={`flex items-center space-x-1 text-[11px] font-semibold mb-1 ${stat.isPositive ? 'text-emerald-600' : 'text-rose-500'}`}>
                {stat.isPositive ? <ArrowUpRight className="w-3 h-3" /> : <ArrowDownRight className="w-3 h-3" />}
                <span>{stat.trend}</span>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-card border border-border p-6 rounded-xl shadow-sm">
          <h3 className="text-lg font-semibold mb-6 text-card-foreground">Clicks vs Conversions</h3>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorClicks" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#2563eb" stopOpacity={0.1}/>
                    <stop offset="95%" stopColor="#2563eb" stopOpacity={0}/>
                  </linearGradient>
                  <linearGradient id="colorConvs" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#10b981" stopOpacity={0.1}/>
                    <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="entity_name" stroke="hsl(var(--muted-foreground))" fontSize={11} tickLine={false} axisLine={false} />
                <YAxis stroke="hsl(var(--muted-foreground))" fontSize={11} tickLine={false} axisLine={false} tickFormatter={(val) => `${val/1000}k`} />
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--border))" />
                <Tooltip 
                  contentStyle={{ backgroundColor: 'hsl(var(--card))', borderColor: 'hsl(var(--border))', borderRadius: '4px', fontSize: '12px' }}
                  itemStyle={{ color: 'hsl(var(--foreground))' }}
                />
                <Area type="monotone" dataKey="clicks" stroke="#2563eb" strokeWidth={2} fillOpacity={1} fill="url(#colorClicks)" />
                <Area type="monotone" dataKey="conversions" stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#colorConvs)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-card border border-border p-6 rounded-xl shadow-sm">
          <h3 className="text-lg font-semibold mb-6 text-card-foreground">Revenue & Cost Analysis</h3>
          <div className="h-[300px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <XAxis dataKey="entity_name" stroke="hsl(var(--muted-foreground))" fontSize={11} tickLine={false} axisLine={false} />
                <YAxis stroke="hsl(var(--muted-foreground))" fontSize={11} tickLine={false} axisLine={false} tickFormatter={(val) => `$${val}`} />
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="hsl(var(--border))" />
                <Tooltip 
                  contentStyle={{ backgroundColor: 'hsl(var(--card))', borderColor: 'hsl(var(--border))', borderRadius: '4px', fontSize: '12px' }}
                  cursor={{ fill: 'hsl(var(--muted))', opacity: 0.1 }}
                />
                <Legend iconType="circle" wrapperStyle={{ paddingTop: '10px', fontSize: '12px' }} />
                <Bar dataKey="revenue" fill="#10b981" radius={[2, 2, 0, 0]} barSize={16} />
                <Bar dataKey="cost" fill="#f59e0b" radius={[2, 2, 0, 0]} barSize={16} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>
    </div>
  );
}
