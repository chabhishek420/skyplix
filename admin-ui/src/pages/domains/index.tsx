import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Globe, Trash2, Loader2 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

type Domain = {
  id: string;
  domain: string;
  campaign_id?: string;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<Domain>();

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
    cell: info => <div className="text-xs text-muted-foreground">{info.getValue() || 'Direct Access / Global'}</div>,
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
    cell: ({ row }) => <DomainActions domain={row.original} />,
  }),
];

function DomainActions({ domain }: { domain: Domain }) {
  const queryClient = useQueryClient();
  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/domains/${domain.id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['domains'] }),
  });

  return (
    <div className="flex justify-end">
      <button
        onClick={() => deleteMutation.mutate()}
        disabled={deleteMutation.isPending}
        className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md disabled:opacity-50"
        title="Remove Domain"
      >
        {deleteMutation.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
      </button>
    </div>
  );
}

export function Domains() {
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ['domains'],
    queryFn: async () => {
      const res = await api.get('/domains');
      return res.data;
    }
  });

  const createMutation = useMutation({
    mutationFn: (domain: string) => api.post('/domains', { domain, state: 'active' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['domains'] }),
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Domains"
        description="Attach custom hostnames to campaigns or global routing."
        icon={Globe}
        onAdd={() => {
          const domain = prompt('Enter domain (e.g. track.example.com):');
          if (domain) createMutation.mutate(domain);
        }}
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
