import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Network, Link as LinkIcon, Trash2, Loader2 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

type AffiliateNetwork = {
  id: string;
  name: string;
  postback_url: string;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<AffiliateNetwork>();

const columns: ColumnDef<AffiliateNetwork, any>[] = [
  columnHelper.accessor('name', {
    header: 'Network Name',
    cell: info => <div className="font-semibold text-foreground">{info.getValue()}</div>,
  }),
  columnHelper.accessor('postback_url', {
    header: 'Postback URL',
    cell: info => <div className="text-xs font-mono text-muted-foreground truncate max-w-[250px]">{info.getValue() || 'Not configured'}</div>,
  }),
  columnHelper.accessor('state', {
    header: 'Status',
    cell: info => (
      <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${info.getValue() === 'active' ? 'bg-green-500/10 text-green-500' : 'bg-muted text-muted-foreground'}`}>
        {info.getValue()}
      </span>
    ),
  }),
  columnHelper.accessor('id', {
    id: 'actions',
    header: '',
    cell: ({ row }) => <NetworkActions network={row.original} />,
  }),
];

function NetworkActions({ network }: { network: AffiliateNetwork }) {
  const queryClient = useQueryClient();

  const handleCopyPostback = async () => {
    try {
      const res = await api.get(`/affiliate_networks/${network.id}/postback_url`);
      await navigator.clipboard.writeText(res.data.postback_url);
      alert('Postback URL copied to clipboard!');
    } catch (err) {
      console.error(err);
      alert('Failed to fetch postback URL');
    }
  };

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/affiliate_networks/${network.id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['networks'] }),
  });

  return (
    <div className="flex justify-end space-x-1">
      <button
        onClick={handleCopyPostback}
        className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md"
        title="Copy Postback URL"
      >
        <LinkIcon className="w-4 h-4" />
      </button>
      <button
        onClick={() => { if(confirm('Delete network?')) deleteMutation.mutate() }}
        disabled={deleteMutation.isPending}
        className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md disabled:opacity-50"
        title="Delete"
      >
        {deleteMutation.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
      </button>
    </div>
  );
}

export function Networks() {
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ['networks'],
    queryFn: async () => {
      const res = await api.get('/affiliate_networks');
      return res.data;
    }
  });

  const createMutation = useMutation({
    mutationFn: (name: string) => api.post('/affiliate_networks', { name, state: 'active' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['networks'] }),
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Affiliate Networks"
        description="Integrate with external offer providers via Postback S2S."
        icon={Network}
        onAdd={() => {
          const name = prompt('Enter network name:');
          if (name) createMutation.mutate(name);
        }}
        addLabel="Add Network"
      />
      
      <DataTable 
        columns={columns} 
        data={data || []} 
        isLoading={isLoading}
        emptyMessage="Integrate your first affiliate network."
      />
    </div>
  );
}
