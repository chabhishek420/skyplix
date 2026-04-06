import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { BarChart2, TrendingUp, Globe, Target } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';

type StatRow = {
  dimensions: Record<string, string>;
  clicks: number;
  unique_clicks: number;
  conversions: number;
  revenue: number;
  cr: number;
  roi: number;
};

const columnHelper = createColumnHelper<StatRow>();

const getColumns = (dimKey: string): ColumnDef<StatRow, any>[] => [
  columnHelper.accessor('dimensions', {
    header: dimKey.charAt(0).toUpperCase() + dimKey.slice(1),
    cell: info => <div className="font-bold text-slate-900">{info.getValue()[dimKey] || 'Unknown'}</div>,
  }),
  columnHelper.accessor('clicks', {
    header: 'Clicks',
    cell: info => <div className="tabular-nums text-right">{info.getValue().toLocaleString()}</div>,
  }),
  columnHelper.accessor('conversions', {
    header: 'Conv.',
    cell: info => <div className="tabular-nums text-right font-medium">{info.getValue().toLocaleString()}</div>,
  }),
  columnHelper.accessor('revenue', {
    header: 'Revenue',
    cell: info => <div className="tabular-nums text-right text-emerald-600 font-bold">${info.getValue().toFixed(2)}</div>,
  }),
  columnHelper.accessor('cr', {
    header: 'CR%',
    cell: info => <div className="tabular-nums text-right">{info.getValue().toFixed(2)}%</div>,
  }),
  columnHelper.accessor('roi', {
    header: 'ROI%',
    cell: info => <div className={`tabular-nums text-right font-bold ${info.getValue() >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>{info.getValue().toFixed(1)}%</div>,
  }),
];

export function Stats() {
  const [activeTab, setActiveTab] = useState<'campaigns' | 'offers' | 'geo'>('campaigns');

  const { data, isLoading, isError } = useQuery({
    queryKey: ['stats', activeTab],
    queryFn: async () => {
      const res = await api.get(`/stats/${activeTab}?preset=today`);
      return res.data?.rows || [];
    },
    retry: 1,
  });

  const tabs = [
    { id: 'campaigns', label: 'By Campaign', icon: TrendingUp },
    { id: 'offers', label: 'By Offer', icon: Target },
    { id: 'geo', label: 'By Country', icon: Globe },
  ];

  const dimMap: Record<string, string> = {
    campaigns: 'campaign',
    offers: 'offer',
    geo: 'country'
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <PageHeader
        title="Analytics Drilldown"
        description="Deep dive into traffic performance across different dimensions."
        icon={BarChart2}
        onAdd={() => {}}
        addLabel=""
      />

      {isError && (
        <div className="bg-rose-50 border border-rose-100 text-rose-600 px-4 py-3 rounded-lg text-sm font-medium">
          Failed to load analytics data. Please check your connection.
        </div>
      )}

      <div className="flex space-x-1 bg-white p-1 rounded-xl border border-slate-200 whisper-shadow max-w-md">
        {tabs.map(tab => (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id as any)}
            className={`flex-1 flex items-center justify-center gap-2 py-2.5 text-xs font-bold uppercase tracking-wider rounded-lg transition-all ${
              activeTab === tab.id ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
            }`}
          >
            <tab.icon className="w-3.5 h-3.5" />
            {tab.label}
          </button>
        ))}
      </div>

      <DataTable
        columns={getColumns(dimMap[activeTab])}
        data={data || []}
        isLoading={isLoading}
        emptyMessage={`No ${activeTab} data for this period.`}
      />
    </div>
  );
}
