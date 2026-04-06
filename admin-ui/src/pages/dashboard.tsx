import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { 
  AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
  BarChart, Bar
} from 'recharts';
import { ArrowUpRight, ArrowDownRight, MousePointerClick, Target, DollarSign, Activity, Edit3 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface SummaryData {
  total_clicks: number;
  total_conversions: number;
  revenue: number;
  roi: number;
}

interface CampaignRow {
  entity_name: string;
  status: string;
  clicks: number;
  conversions: number;
  revenue: number;
  roi: number;
}

export function Dashboard() {
  const { data: reportData, isLoading, isError } = useQuery({
    queryKey: ['dashboard-report'],
    queryFn: async () => {
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

  const summary: SummaryData = {
    total_clicks: reportData?.summary?.clicks || 0,
    total_conversions: reportData?.summary?.conversions || 0,
    revenue: reportData?.summary?.revenue || 0,
    roi: reportData?.summary?.roi || 0,
  };

  const rows: CampaignRow[] = (reportData?.rows || []).map((row: any) => ({
    entity_name: row.dimensions.campaign_name || row.dimensions.campaign || 'Unknown',
    status: 'active',
    clicks: row.clicks,
    conversions: row.conversions,
    revenue: row.revenue,
    roi: row.roi,
  }));

  const chartData = (reportData?.rows || []).filter((r: any) => r.dimensions.day).map((r: any) => ({
    name: r.dimensions.day,
    clicks: r.clicks,
    conversions: r.conversions,
    revenue: r.revenue,
    cost: r.cost,
  })).sort((a: any, b: any) => a.name.localeCompare(b.name));

  const finalChartData = chartData.length > 0 ? chartData : [
    { name: 'No Data', clicks: 0, conversions: 0, revenue: 0, cost: 0 },
  ];

  const stats = [
    { label: 'Total Clicks', value: summary.total_clicks.toLocaleString(), trend: '+12.4%', isPositive: true, color: 'border-t-blue-600', icon: MousePointerClick },
    { label: 'Conversions', value: summary.total_conversions.toLocaleString(), trend: '+5.2%', isPositive: true, color: 'border-t-emerald-500', icon: Target },
    { label: 'Revenue', value: `$${summary.revenue.toLocaleString()}`, trend: '+8.1%', isPositive: true, color: 'border-t-emerald-600', icon: DollarSign },
    { label: 'Avg ROI', value: `${summary.roi}%`, trend: '-1.4%', isPositive: false, color: 'border-t-slate-800', icon: Activity },
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
              <AreaChart data={finalChartData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
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
              <BarChart data={finalChartData} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
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

      <div className="bg-white rounded border border-slate-100 whisper-shadow overflow-hidden">
        <div className="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
          <h3 className="text-[12px] font-bold text-slate-800 uppercase tracking-tight leading-none">Recent Campaigns</h3>
          <button className="text-[11px] font-bold text-blue-600 hover:underline">View All Records</button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead className="bg-[#fcfdfe]">
              <tr className="border-b border-slate-50">
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Campaign Name</th>
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right whitespace-nowrap">Clicks</th>
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right whitespace-nowrap">Conv.</th>
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right">Revenue</th>
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-right">ROI%</th>
                <th className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider text-center">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-50">
              {rows.map((row: CampaignRow, i: number) => (
                <tr key={i} className={`${i % 2 === 1 ? 'bg-[#fcfdfe]' : ''} hover:bg-slate-50 transition-colors`}>
                  <td className="px-6 py-3 text-[13px] font-medium text-slate-900 border-r border-slate-50/50">{row.entity_name}</td>
                  <td className="px-6 py-3">
                    <Badge variant="outline" className={`text-[10px] font-bold uppercase tracking-wide border-0 shadow-none px-2 py-0 h-5 leading-none ${
                       row.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'
                    }`}>
                      {row.status}
                    </Badge>
                  </td>
                  <td className="px-6 py-3 text-[13px] text-slate-600 tabular-nums text-right font-medium">{row.clicks.toLocaleString()}</td>
                  <td className="px-6 py-3 text-[13px] text-slate-600 tabular-nums text-right font-medium">{row.conversions.toLocaleString()}</td>
                  <td className="px-6 py-3 text-[13px] font-semibold text-slate-900 tabular-nums text-right">${row.revenue.toLocaleString()}</td>
                  <td className={`px-6 py-3 text-[13px] font-bold tabular-nums text-right ${row.roi > 200 ? 'text-emerald-600' : 'text-slate-500'}`}>{row.roi}%</td>
                  <td className="px-6 py-3 text-center">
                    <button className="p-1 hover:bg-slate-100 rounded transition-colors text-slate-400 hover:text-blue-600">
                      <Edit3 className="w-3.5 h-3.5" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
