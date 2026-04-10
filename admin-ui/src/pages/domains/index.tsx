import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Globe, Trash2, Edit3 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

type Domain = {
  id: string;
  domain: string;
  campaign_id?: string;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<Domain>();

function ActionsCell({ id }: { id: string }) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/domains/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['domains'] }),
  });

  return (
    <div className="flex justify-end space-x-1">
      <button
        onClick={() => navigate(`/domains/${id}`)}
        className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Edit"
      >
        <Edit3 className="w-4 h-4" />
      </button>
      <button
        onClick={() => {
          if (confirm('Are you sure you want to remove this domain?')) {
            deleteMutation.mutate();
          }
        }}
        className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md" title="Remove Domain"
      >
        <Trash2 className="w-4 h-4" />
      </button>
    </div>
  );
}

const columns: ColumnDef<Domain, any>[] = [
  columnHelper.accessor('domain', {
    header: 'Domain Name',
    cell: info => (
      <div className="flex items-center space-x-2">
        <Globe className="w-4 h-4 text-muted-foreground" />
        <span className="font-semibold text-foreground">{info.getValue()}</span>
      </div>
    ),
  }),
  columnHelper.accessor('campaign_id', {
    header: 'Attached Campaign',
    cell: info => <div className="text-xs text-muted-foreground font-mono">{info.getValue() || 'Direct Access / Global'}</div>,
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

export function Domains() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['domains'],
    queryFn: async () => {
      const res = await api.get('/domains');
      return res.data || [];
    }
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Domains"
        description="Attach custom hostnames to campaigns or global routing."
        icon={Globe}
        onAdd={() => navigate('/domains/new')}
        addLabel="Add Domain"
      />
      
      <DataTable 
        columns={columns} 
        data={data || []} 
        isLoading={isLoading}
        emptyMessage="Park your tracking domains here."
      />
    </div>
  );
}
