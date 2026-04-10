import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Target, Link as LinkIcon, Trash2, ExternalLink, Edit3 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';

type Offer = {
  id: string;
  name: string;
  url: string;
  affiliate_network_id?: string;
  payout: number;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<Offer>();

function ActionsCell({ id, url }: { id: string, url: string }) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/offers/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['offers'] }),
  });

  return (
    <div className="flex justify-center space-x-1">
      <button
        onClick={() => navigate(`/offers/${id}`)}
        className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Edit"
      >
        <Edit3 className="w-3.5 h-3.5" />
      </button>
      <button
        onClick={() => {
          navigator.clipboard.writeText(url);
        }}
        className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Copy URL"
      >
        <LinkIcon className="w-3.5 h-3.5" />
      </button>
      <button
        onClick={() => {
          if (confirm('Are you sure you want to delete this offer?')) {
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

const columns: ColumnDef<Offer, any>[] = [
  columnHelper.accessor('name', {
    header: 'Offer Name',
    cell: info => (
      <div>
        <div className="font-bold text-slate-900 tracking-tight">{info.getValue()}</div>
        <div className="text-[11px] text-slate-400 truncate max-w-[300px] flex items-center gap-1 mt-1 font-medium italic underline decoration-slate-100 underline-offset-2">
          <ExternalLink className="w-2.5 h-2.5" />
          {info.row.original.url}
        </div>
      </div>
    ),
  }),
  columnHelper.accessor('payout', {
    header: 'Payout',
    cell: info => <div className="font-mono text-emerald-600 font-bold tabular-nums text-right px-4">${info.getValue().toFixed(2)}</div>,
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
    id: 'actions',
    header: () => <div className="text-center">Actions</div>,
    cell: info => <ActionsCell id={info.getValue()} url={info.row.original.url} />,
  }),
];

export function Offers() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['offers'],
    queryFn: async () => {
      const res = await api.get('/offers');
      return res.data || [];
    }
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Offers"
        description="Target destinations for your traffic flows."
        icon={Target}
        onAdd={() => navigate('/offers/new')}
        addLabel="Create Offer"
      />
      
      <DataTable 
        columns={columns} 
        data={data || []} 
        isLoading={isLoading}
        emptyMessage="No offers configured yet."
      />
    </div>
  );
}
