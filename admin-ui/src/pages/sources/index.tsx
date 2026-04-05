import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useNavigate } from 'react-router-dom';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Activity, LayoutGrid, Trash2, Edit3 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';

type TrafficSource = {
  id: string;
  name: string;
  postback_url: string;
  params: Record<string, string>;
  state: 'active' | 'disabled';
};

const columnHelper = createColumnHelper<TrafficSource>();

function ActionsCell({ id }: { id: string }) {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/traffic_sources/${id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['sources'] }),
  });

  return (
    <div className="flex justify-end space-x-1">
      <button
        onClick={() => navigate(`/sources/${id}`)}
        className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Edit"
      >
        <Edit3 className="w-4 h-4" />
      </button>
      <button
        onClick={() => navigate(`/sources/${id}`)}
        className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/5 rounded-md" title="Configure Params"
      >
        <LayoutGrid className="w-4 h-4" />
      </button>
      <button
        onClick={() => {
          if (confirm('Are you sure you want to delete this traffic source?')) {
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

const columns: ColumnDef<TrafficSource, any>[] = [
  columnHelper.accessor('name', {
    header: 'Source Name',
    cell: info => <div className="font-semibold text-foreground">{info.getValue()}</div>,
  }),
  columnHelper.accessor('params', {
    header: 'Parameters',
    cell: info => (
      <div className="flex gap-1 flex-wrap">
        {Object.keys(info.getValue() || {}).slice(0, 3).map(key => (
          <span key={key} className="px-1.5 py-0.5 bg-muted rounded text-[10px] uppercase font-mono text-muted-foreground border border-border">
            {key}
          </span>
        ))}
        {Object.keys(info.getValue() || {}).length > 3 && (
          <span className="px-1.5 py-0.5 text-[10px] text-muted-foreground">+{Object.keys(info.getValue()).length - 3} more</span>
        )}
        {Object.keys(info.getValue() || {}).length === 0 && (
          <span className="text-[10px] text-slate-400 italic">No custom params</span>
        )}
      </div>
    ),
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

export function Sources() {
  const navigate = useNavigate();
  const { data, isLoading } = useQuery({
    queryKey: ['sources'],
    queryFn: async () => {
      const res = await api.get('/traffic_sources');
      return res.data || [];
    }
  });

  return (
    <div className="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
      <PageHeader
        title="Traffic Sources"
        description="Configure dynamic parameters and postback links for incoming traffic."
        icon={Activity}
        onAdd={() => navigate('/sources/new')}
        addLabel="Add Source"
      />
      
      <DataTable 
        columns={columns} 
        data={data || []} 
        isLoading={isLoading}
        emptyMessage="Setup your first traffic source (Facebook, Google, etc)."
      />
    </div>
  );
}
