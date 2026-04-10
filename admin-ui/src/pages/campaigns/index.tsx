import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { 
  createColumnHelper,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { Megaphone, Plus, Link as LinkIcon, Trash2, Filter, ArrowUpDown, Edit3 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

type Campaign = {
  id: string;
  name: string;
  state: 'active' | 'disabled';
  clicks?: number;
  conversions?: number;
  revenue?: number;
  alias?: string;
};

const columnHelper = createColumnHelper<Campaign>();

function ActionsCell({ id, alias }: { id: string, alias: string }) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/campaigns/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['campaigns-with-stats'] }),
  });

  return (
    <div className="flex justify-center space-x-1">
      <button
        onClick={() => navigate(`/campaigns/${id}`)}
        className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Edit"
      >
        <Edit3 className="w-3.5 h-3.5" />
      </button>
      <button
        onClick={() => {
          const url = `${window.location.origin}/${alias}`;
          navigator.clipboard.writeText(url);
        }}
        className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Copy URL"
      >
        <LinkIcon className="w-3.5 h-3.5" />
      </button>
      <button
        onClick={() => {
          if (confirm('Are you sure you want to delete this campaign?')) {
            deleteMutation.mutate();
          }
        }}
        className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors hover:bg-rose-50 rounded" title="Delete"
      >
        <Trash2 className="w-3.5 h-3.5" />
      </button>
    </div>
  );
}

const columns = [
  columnHelper.accessor('name', {
    header: 'Campaign Name',
    cell: info => <div className="font-bold text-slate-900 tracking-tight">{info.getValue()}</div>,
  }),
  columnHelper.accessor('state', {
    header: 'Status',
    cell: info => (
      <Badge variant="outline" className={`text-[10px] font-bold uppercase tracking-wide border-0 shadow-none px-2 py-0 h-5 leading-none ${
        info.getValue() === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'
      }`}>
        {info.getValue()}
      </Badge>
    ),
  }),
  columnHelper.accessor('clicks', {
    header: () => <div className="text-right whitespace-nowrap">Clicks</div>,
    cell: info => <div className="text-right tabular-nums font-medium text-slate-600">{info.getValue()?.toLocaleString() || 0}</div>,
  }),
  columnHelper.accessor('conversions', {
    header: () => <div className="text-right whitespace-nowrap">Conv.</div>,
    cell: info => <div className="text-right tabular-nums font-medium text-slate-600">{info.getValue()?.toLocaleString() || 0}</div>,
  }),
  columnHelper.accessor('revenue', {
    header: () => <div className="text-right whitespace-nowrap">Revenue</div>,
    cell: info => <div className="text-right tabular-nums font-bold text-slate-900">${info.getValue()?.toLocaleString() || '0.00'}</div>,
  }),
  columnHelper.accessor('id', {
    id: 'actions',
    header: () => <div className="text-center">Actions</div>,
    cell: info => <ActionsCell id={info.getValue()} alias={(info.row.original as any).alias} />,
  }),
];

export function Campaigns() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['campaigns-with-stats'],
    queryFn: async () => {
      // Fetch both campaigns and stats to merge them
      const [campaignsRes, statsRes] = await Promise.all([
        api.get('/campaigns'),
        api.get('/reports?group_by=campaign')
      ]);

      const campaigns = campaignsRes.data || [];
      const statsRows = statsRes.data?.rows || [];

      // Optimize join performance with a Map for O(1) lookups
      const statsMap = new Map(statsRows.map((s: any) => [s.dimensions.campaign, s]));

      return campaigns.map((c: any) => {
        const s = statsMap.get(c.id) as any;
        return {
          ...c,
          clicks: s?.clicks || 0,
          conversions: s?.conversions || 0,
          revenue: s?.revenue || 0,
        };
      });
    }
  });

  const tableData = data || [];

  const table = useReactTable({
    data: tableData,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">Campaigns</h1>
          <p className="text-slate-500 text-[13px] mt-1 font-medium italic underline decoration-slate-200 underline-offset-4">Tracking endpoints and logic layers</p>
        </div>
        <div className="flex gap-2">
          <button className="flex items-center space-x-2 px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-600 text-[12px] font-bold whisper-shadow hover:bg-slate-50 transition-all">
            <Filter className="w-3.5 h-3.5" />
            <span>Filter</span>
          </button>
          <button
            onClick={() => navigate('/campaigns/new')}
            className="flex items-center space-x-2 bg-[#2563eb] text-white px-4 py-1.5 rounded text-[12px] font-bold shadow-sm shadow-blue-200 hover:bg-blue-700 transition-all active:scale-95"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Create Campaign</span>
          </button>
        </div>
      </div>

      <div className="bg-white border border-[#e2e8f0] rounded whisper-shadow overflow-hidden">
        {isLoading ? (
          <div className="p-12 text-center text-slate-400 text-[13px] font-medium animate-pulse">Synchronizing campaign data...</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead className="bg-[#fcfdfe] border-b border-slate-100">
                {table.getHeaderGroups().map(headerGroup => (
                  <tr key={headerGroup.id}>
                    {headerGroup.headers.map(header => (
                      <th key={header.id} className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">
                        <div className="flex items-center gap-1.5 group cursor-pointer hover:text-slate-600 transition-colors">
                          {header.isPlaceholder
                            ? null
                            : flexRender(
                                header.column.columnDef.header,
                                header.getContext()
                              )}
                           {header.id !== 'actions' && <ArrowUpDown className="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" />}
                        </div>
                      </th>
                    ))}
                  </tr>
                ))}
              </thead>
              <tbody className="divide-y divide-slate-50/50">
                {table.getRowModel().rows.map((row, i) => (
                  <tr key={row.id} className={`${i % 2 === 1 ? 'bg-[#fcfdfe]' : ''} hover:bg-slate-50 transition-colors`}>
                    {row.getVisibleCells().map(cell => (
                      <td key={cell.id} className="px-6 py-3.5 whitespace-nowrap border-r border-slate-50/50 last:border-0">
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </td>
                    ))}
                  </tr>
                ))}
                {tableData.length === 0 && (
                  <tr>
                    <td colSpan={columns.length} className="px-6 py-16 text-center">
                      <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-50 mb-3">
                        <Megaphone className="w-6 h-6 text-slate-300" />
                      </div>
                      <p className="text-slate-400 text-[13px] font-medium">No campaigns found. Start by creating a tracking link.</p>
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
