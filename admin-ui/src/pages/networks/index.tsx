import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Network, Trash2, Edit3, Settings } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

type AffiliateNetwork = {
  id: string;
  name: string;
  postback_url: string;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<AffiliateNetwork>();

function ActionsCell({ id }: { id: string }) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/affiliate_networks/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['networks'] }),
  });

  return (
    <div className="flex justify-end space-x-1">
      <button
        onClick={() => navigate(`/networks/${id}`)}
        className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Edit"
      >
        <Edit3 className="w-4 h-4" />
      </button>
      <button
        onClick={() => navigate(`/networks/${id}`)}
        className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Postback Config"
      >
        <Settings className="w-4 h-4" />
      </button>
      <button
        onClick={() => {
          if (confirm('Are you sure you want to delete this network?')) {
            deleteMutation.mutate();
          }
        }}
        className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md" title="Delete"
      >
        <Trash2 className="w-4 h-4" />
      </button>
    </div>
  );
}

const columns: ColumnDef<AffiliateNetwork, any>[] = [
  columnHelper.accessor('name', {
    header: 'Network Name',
    cell: info => <div className="font-semibold text-foreground">{info.getValue()}</div>,
  }),
  columnHelper.accessor('postback_url', {
    header: 'Postback URL',
    cell: info => <div className="text-xs font-mono text-muted-foreground truncate max-w-[250px]">{info.getValue() || 'Auto-generated template'}</div>,
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
    cell: info => <ActionsCell id={info.getValue()} />,
  }),
];

export function Networks() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['networks'],
    queryFn: async () => {
      const res = await api.get('/affiliate_networks');
      return res.data || [];
    }
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Affiliate Networks"
        description="Integrate with external offer providers via Postback S2S."
        icon={Network}
        onAdd={() => navigate('/networks/new')}
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
