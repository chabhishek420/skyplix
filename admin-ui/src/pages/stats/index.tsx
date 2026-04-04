import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import {
  createColumnHelper,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { BarChart3, Globe, Target, Megaphone } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type StatRow = {
  dimensions: Record<string, string>;
  clicks: number;
  conversions: number;
  revenue: number;
  cost: number;
  profit: number;
  cr: number;
  epc: number;
};

const columnHelper = createColumnHelper<StatRow>();

const columns = (dim: string) => [
  columnHelper.accessor(row => row.dimensions[dim + '_name'] || row.dimensions[dim] || 'Unknown', {
    id: 'name',
    header: dim.charAt(0).toUpperCase() + dim.slice(1),
    cell: info => <div className="font-bold text-slate-900">{info.getValue()}</div>,
  }),
  columnHelper.accessor('clicks', {
    header: 'Clicks',
    cell: info => <div className="text-right tabular-nums">{info.getValue().toLocaleString()}</div>,
  }),
  columnHelper.accessor('conversions', {
    header: 'Conv',
    cell: info => <div className="text-right tabular-nums">{info.getValue().toLocaleString()}</div>,
  }),
  columnHelper.accessor('cr', {
    header: 'CR%',
    cell: info => <div className="text-right tabular-nums font-medium text-blue-600">{info.getValue().toFixed(2)}%</div>,
  }),
  columnHelper.accessor('revenue', {
    header: 'Revenue',
    cell: info => <div className="text-right tabular-nums font-semibold text-slate-900">${info.getValue().toLocaleString()}</div>,
  }),
  columnHelper.accessor('epc', {
    header: 'EPC',
    cell: info => <div className="text-right tabular-nums font-medium text-emerald-600">${info.getValue().toFixed(3)}</div>,
  }),
  columnHelper.accessor('profit', {
    header: 'Profit',
    cell: info => <div className={`text-right tabular-nums font-bold ${info.getValue() >= 0 ? 'text-emerald-600' : 'text-rose-600'}`}>${info.getValue().toLocaleString()}</div>,
  }),
];

function StatsTable({ type }: { type: string }) {
  const { data, isLoading } = useQuery({
    queryKey: ['stats', type],
    queryFn: async () => {
      const res = await api.get(`/stats/${type}`);
      return res.data.rows as StatRow[];
    }
  });

  const table = useReactTable({
    data: data || [],
    columns: columns(type === 'geo' ? 'country' : type.slice(0, -1)),
    getCoreRowModel: getCoreRowModel(),
  });

  if (isLoading) return <div className="p-12 text-center text-slate-400">Loading stats...</div>;

  return (
    <div className="bg-white border border-slate-200 rounded-lg overflow-hidden whisper-shadow">
      <div className="overflow-x-auto">
        <table className="w-full text-left">
          <thead className="bg-slate-50 border-b border-slate-200">
            {table.getHeaderGroups().map(headerGroup => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map(header => (
                  <th key={header.id} className="px-6 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    {flexRender(header.column.columnDef.header, header.getContext())}
                  </th>
                ))}
              </tr>
            ))}
          </thead>
          <tbody className="divide-y divide-slate-100">
            {table.getRowModel().rows.map(row => (
              <tr key={row.id} className="hover:bg-slate-50 transition-colors">
                {row.getVisibleCells().map(cell => (
                  <td key={cell.id} className="px-6 py-3.5 whitespace-nowrap text-[13px]">
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export function Stats() {
  return (
    <div className="space-y-6 animate-in fade-in duration-500 pb-12">
      <PageHeader
        title="Analytics Drilldown"
        description="Performance metrics across campaigns, offers, and geographies."
        icon={BarChart3}
      />

      <Tabs defaultValue="campaigns" className="space-y-6">
        <TabsList className="bg-white border border-slate-200 p-1">
          <TabsTrigger value="campaigns" className="flex gap-2">
            <Megaphone className="w-3.5 h-3.5" />
            Campaigns
          </TabsTrigger>
          <TabsTrigger value="offers" className="flex gap-2">
            <Target className="w-3.5 h-3.5" />
            Offers
          </TabsTrigger>
          <TabsTrigger value="geo" className="flex gap-2">
            <Globe className="w-3.5 h-3.5" />
            Geography
          </TabsTrigger>
        </TabsList>

        <TabsContent value="campaigns">
          <StatsTable type="campaigns" />
        </TabsContent>
        <TabsContent value="offers">
          <StatsTable type="offers" />
        </TabsContent>
        <TabsContent value="geo">
          <StatsTable type="geo" />
        </TabsContent>
      </Tabs>
    </div>
  );
}
