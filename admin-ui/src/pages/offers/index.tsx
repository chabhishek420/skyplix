import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Target, Link as LinkIcon, Trash2, Copy, ExternalLink, Loader2 } from 'lucide-react';
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

// Explicitly typing the columns array with ColumnDef<T, any>[] to avoid 
// inference locking the TValue type to only one property type (e.g. number).
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
    cell: info => <div className="font-mono text-emerald-600 font-bold tabular-nums">${info.getValue().toFixed(2)}</div>,
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
    cell: ({ row }) => <OfferActions offer={row.original} />,
  }),
];

function OfferActions({ offer }: { offer: Offer }) {
  const queryClient = useQueryClient();
  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/offers/${offer.id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['offers'] }),
  });

  return (
    <div className="flex justify-center space-x-1">
      <button className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Copy URL">
        <LinkIcon className="w-3.5 h-3.5" />
      </button>
      <button className="p-1.5 text-slate-400 hover:text-blue-600 transition-colors hover:bg-slate-50 rounded" title="Clone">
        <Copy className="w-3.5 h-3.5" />
      </button>
      <button
        onClick={() => deleteMutation.mutate()}
        disabled={deleteMutation.isPending}
        className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors hover:bg-rose-50 rounded disabled:opacity-50"
        title="Delete"
      >
        {deleteMutation.isPending ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
      </button>
    </div>
  );
}

export function Offers() {
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ['offers'],
    queryFn: async () => {
      const res = await api.get('/offers');
      return res.data;
    }
  });

  const createMutation = useMutation({
    mutationFn: (name: string) => api.post('/offers', { name, url: 'https://example.com', state: 'active', payout: 0 }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['offers'] }),
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Offers"
        description="Target destinations for your traffic flows."
        icon={Target}
        onAdd={() => {
          const name = prompt('Enter offer name:');
          if (name) createMutation.mutate(name);
        }}
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
