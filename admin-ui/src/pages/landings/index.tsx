import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper } from '@tanstack/react-table';
import { FileBox, Link as LinkIcon, Trash2, Copy, ExternalLink, Loader2 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

type Landing = {
  id: string;
  name: string;
  url: string;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<Landing>();

const columns = [
  columnHelper.accessor('name', {
    header: 'Landing Page',
    cell: info => (
      <div>
        <div className="font-medium text-foreground">{info.getValue()}</div>
        <div className="text-xs text-muted-foreground flex items-center gap-1 mt-0.5 max-w-[300px] truncate">
          <ExternalLink className="w-3 h-3 flex-shrink-0" />
          {info.row.original.url}
        </div>
      </div>
    ),
  }),
  columnHelper.accessor('state', {
    header: 'Status',
    cell: info => (
      <span className={`px-2.5 py-0.5 rounded-md text-[11px] uppercase tracking-wider font-bold border ${info.getValue() === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-muted text-muted-foreground border-border'}`}>
        {info.getValue()}
      </span>
    ),
  }),
  columnHelper.accessor('id', {
    id: 'actions',
    header: '',
    cell: ({ row }) => <LandingActions landing={row.original} />,
  }),
];

function LandingActions({ landing }: { landing: Landing }) {
  const queryClient = useQueryClient();
  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/landings/${landing.id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['landings'] }),
  });

  return (
    <div className="flex justify-end space-x-1">
      <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Copy URL">
        <LinkIcon className="w-4 h-4" />
      </button>
      <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Clone">
        <Copy className="w-4 h-4" />
      </button>
      <button
        onClick={() => deleteMutation.mutate()}
        disabled={deleteMutation.isPending}
        className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/5 rounded-md disabled:opacity-50"
        title="Delete"
      >
        {deleteMutation.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Trash2 className="w-4 h-4" />}
      </button>
    </div>
  );
}

export function Landings() {
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ['landings'],
    queryFn: async () => {
      const res = await api.get('/landings');
      return res.data;
    }
  });

  const createMutation = useMutation({
    mutationFn: (name: string) => api.post('/landings', { name, url: 'https://example.com', state: 'active' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['landings'] }),
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Landings"
        description="Landing pages for Level 1 → Level 2 click conversion."
        icon={FileBox}
        onAdd={() => {
          const name = prompt('Enter landing name:');
          if (name) createMutation.mutate(name);
        }}
        addLabel="Create Landing"
      />
      
      <DataTable 
        columns={columns} 
        data={data || []} 
        isLoading={isLoading}
        emptyMessage="No landing pages configured yet."
      />
    </div>
  );
}
