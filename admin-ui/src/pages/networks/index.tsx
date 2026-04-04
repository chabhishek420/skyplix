import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Network, Link as LinkIcon, Trash2 } from 'lucide-react';
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
    cell: () => (
      <div className="flex justify-end space-x-1">
        <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Configure">
          <LinkIcon className="w-4 h-4" />
        </button>
        <button className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md" title="Delete">
          <Trash2 className="w-4 h-4" />
        </button>
      </div>
    ),
  }),
];

export function Networks() {
  const { data, isLoading } = useQuery({
    queryKey: ['networks'],
    queryFn: async () => {
      const res = await api.get('/networks');
      return res.data;
    }
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Affiliate Networks"
        description="Integrate with external offer providers via Postback S2S."
        icon={Network}
        onAdd={() => console.log('Add Network')}
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
