import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { 
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  BarChart, Bar
} from 'recharts';
import { ArrowUpRight, ArrowDownRight, MousePointerClick, Target, DollarSign, Activity } from 'lucide-react';
import { format } from 'date-fns';

interface SummaryData {
  total_clicks: number;
  total_conversions: number;
  revenue: number;
  roi: number;
}


export function Dashboard() {
  const { data: reportData, isLoading, isError } = useQuery({
    queryKey: ['dashboard-report'],
    queryFn: async () => {
      const res = await api.get('/stats/summary?preset=today');
      return res.data;
    }
  });

  const { data: chartData } = useQuery({
    queryKey: ['dashboard-chart'],
    queryFn: async () => {
      const res = await api.get('/reports?group_by=day&preset=last_7d');
      return res.data?.rows?.map((row: any) => ({
        name: format(new Date(row.dimensions.day), 'MMM dd'),
        clicks: row.clicks,
        conversions: row.conversions,
        revenue: row.revenue,
        cost: row.cost
      })) || [];
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

  const summary: SummaryData = reportData || { total_clicks: 0, total_conversions: 0, revenue: 0, roi: 0 };

  const stats = [
    { label: 'Total Clicks', value: summary.total_clicks.toLocaleString(), trend: 'Live', isPositive: true, color: 'border-t-blue-600', icon: MousePointerClick },
    { label: 'Conversions', value: summary.total_conversions.toLocaleString(), trend: 'Live', isPositive: true, color: 'border-t-emerald-500', icon: Target },
    { label: 'Revenue', value: `$${summary.revenue.toLocaleString()}`, trend: 'Live', isPositive: true, color: 'border-t-emerald-600', icon: DollarSign },
    { label: 'Avg ROI', value: `${summary.roi.toFixed(1)}%`, trend: 'Live', isPositive: true, color: 'border-t-slate-800', icon: Activity },
  ];

  return (
    <div className="space-y-8 animate-in fade-in duration-500 pb-12">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold tracking-tight text-slate-900 leading-none">Dashboard Overview</h1>
        <div className="inline-flex items-center px-3 py-1 rounded-full bg-white border border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider tabular-nums whisper-shadow">
          Last 7 Days
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, i) => (
          <div key={i} className={`bg-white p-5 rounded border-t-2 border border-slate-100 whisper-shadow transition-all group ${stat.color}`}>
            <div className="flex justify-between items-start mb-2">
              <span className="text-[11px] font-bold text-slate-500 uppercase tracking-widest">{stat.label}</span>
              <stat.icon className="w-4 h-4 text-slate-300 group-hover:text-primary transition-colors" />
            </div>
            <div className="flex items-baseline justify-between">
              <div className="tabular-nums text-2xl font-bold text-slate-900">{stat.value}</div>
              <div className={`flex items-center gap-0.5 text-[11px] font-bold ${stat.isPositive ? 'text-emerald-600' : 'text-rose-600'}`}>
                {stat.isPositive ? <ArrowUpRight className="w-3 h-3" /> : <ArrowDownRight className="w-3 h-3" />}
                {stat.trend}
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div className="bg-white p-6 rounded border border-slate-100 whisper-shadow">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-[12px] font-bold text-slate-800 uppercase tracking-tight">Clicks vs Conversions</h3>
            <div className="flex gap-4">
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-full bg-blue-600"></div>
                <span className="text-[10px] font-bold text-slate-500 uppercase">Clicks</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span className="text-[10px] font-bold text-slate-500 uppercase">Conversions</span>
              </div>
            </div>
          </div>
          <div className="h-[240px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <AreaChart data={chartData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="colorClicks" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#2563eb" stopOpacity={0.08}/>
                    <stop offset="95%" stopColor="#2563eb" stopOpacity={0}/>
                  </linearGradient>
                  <linearGradient id="colorConvs" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#10b981" stopOpacity={0.08}/>
                    <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <XAxis dataKey="name" stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} />
                <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} tickFormatter={(val) => `${val/1000}k`} />
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <Tooltip 
                  contentStyle={{ backgroundColor: '#ffffff', borderColor: '#e2e8f0', borderRadius: '4px', fontSize: '11px', boxShadow: '0 1px 3px rgba(30, 41, 59, 0.04)' }}
                  itemStyle={{ padding: '2px 0' }}
                />
                <Area type="monotone" dataKey="clicks" stroke="#2563eb" strokeWidth={2} fillOpacity={1} fill="url(#colorClicks)" />
                <Area type="monotone" dataKey="conversions" stroke="#10b981" strokeWidth={2} fillOpacity={1} fill="url(#colorConvs)" />
              </AreaChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-white p-6 rounded border border-slate-100 whisper-shadow">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-[12px] font-bold text-slate-800 uppercase tracking-tight">Revenue & Cost Analysis</h3>
            <div className="flex gap-4">
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-sm bg-emerald-600"></div>
                <span className="text-[10px] font-bold text-slate-500 uppercase">Rev</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-2 h-2 rounded-sm bg-amber-500"></div>
                <span className="text-[10px] font-bold text-slate-500 uppercase">Cost</span>
              </div>
            </div>
          </div>
          <div className="h-[240px] w-full">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={chartData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
                <XAxis dataKey="name" stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} />
                <YAxis stroke="#94a3b8" fontSize={10} tickLine={false} axisLine={false} tickFormatter={(val) => `$${val}`} />
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <Tooltip 
                  contentStyle={{ backgroundColor: '#ffffff', borderColor: '#e2e8f0', borderRadius: '4px', fontSize: '11px', boxShadow: '0 1px 3px rgba(30, 41, 59, 0.04)' }}
                  cursor={{ fill: '#f8fafc', opacity: 0.4 }}
                />
                <Bar dataKey="revenue" fill="#10b981" radius={[2, 2, 0, 0]} barSize={12} />
                <Bar dataKey="cost" fill="#f59e0b" radius={[2, 2, 0, 0]} barSize={12} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

    </div>
  );
}
