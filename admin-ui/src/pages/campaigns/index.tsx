import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { 
  createColumnHelper,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { Megaphone, Plus, Link as LinkIcon, Trash2, Copy, Filter, ArrowUpDown, Loader2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

type Campaign = {
  id: string;
  name: string;
  state: 'active' | 'disabled';
  clicks?: number;
  conversions?: number;
  revenue?: number;
};

const columnHelper = createColumnHelper<Campaign>();

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
  columnHelper.accessor('id', {
    id: 'stats',
    header: () => <div className="text-right">Performance</div>,
    cell: () => (
      <div className="flex justify-end items-center gap-4 tabular-nums text-[13px]">
        <div className="text-slate-500"><span className="text-slate-900 font-semibold">12.4k</span> <span className="text-[10px] uppercase font-bold text-slate-400">Clicks</span></div>
        <div className="text-slate-500"><span className="text-slate-900 font-semibold">412</span> <span className="text-[10px] uppercase font-bold text-slate-400">Conv</span></div>
      </div>
    ),
  }),
  columnHelper.accessor('id', {
    id: 'actions',
    header: () => <div className="text-center">Actions</div>,
    cell: ({ row }) => <CampaignActions campaign={row.original} />,
  }),
];

function CampaignActions({ campaign }: { campaign: Campaign }) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/campaigns/${campaign.id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['campaigns'] }),
  });

  return (
    <div className="flex justify-center space-x-1">
      <button
        onClick={() => navigate(`/campaigns/${campaign.id}`)}
        className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded"
        title="Edit"
      >
        <LinkIcon className="w-3.5 h-3.5" />
      </button>
      <button className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Clone">
        <Copy className="w-3.5 h-3.5" />
      </button>
      <button
        onClick={() => {
          if (confirm('Delete campaign?')) deleteMutation.mutate();
        }}
        disabled={deleteMutation.isPending}
        className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors hover:bg-rose-50 rounded disabled:opacity-50"
        title="Delete"
      >
        {deleteMutation.isPending ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
      </button>
    </div>
  );
}

export function Campaigns() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['campaigns'],
    queryFn: async () => {
      const res = await api.get('/campaigns');
      return (res.data || []) as Campaign[];
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
