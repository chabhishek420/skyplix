import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Target, Link as LinkIcon, Trash2, Copy, ExternalLink } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

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
        <div className="font-medium text-foreground">{info.getValue()}</div>
        <div className="text-xs text-muted-foreground truncate max-w-[200px] flex items-center gap-1 mt-0.5">
          <ExternalLink className="w-3 h-3" />
          {info.row.original.url}
        </div>
      </div>
    ),
  }),
  columnHelper.accessor('payout', {
    header: 'Payout',
    cell: info => <div className="font-mono text-primary">${info.getValue().toFixed(2)}</div>,
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
        <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Copy URL">
          <LinkIcon className="w-4 h-4" />
        </button>
        <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Clone">
          <Copy className="w-4 h-4" />
        </button>
        <button className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md" title="Delete">
          <Trash2 className="w-4 h-4" />
        </button>
      </div>
    ),
  }),
];

export function Offers() {
  const { data, isLoading } = useQuery({
    queryKey: ['offers'],
    queryFn: async () => {
      const res = await api.get('/offers');
      return res.data;
    }
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Offers"
        description="Target destinations for your traffic flows."
        icon={Target}
        onAdd={() => console.log('Add Offer')}
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
