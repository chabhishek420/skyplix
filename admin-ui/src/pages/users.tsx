import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { createColumnHelper, type ColumnDef } from '@tanstack/react-table';
import { Users as UsersIcon, Trash2, Shield, Loader2 } from 'lucide-react';
import { PageHeader } from '@/components/ui/page-header';
import { DataTable } from '@/components/ui/data-table';
import { Badge } from '@/components/ui/badge';

type User = {
  id: string;
  login: string;
  role: string;
  state: 'active' | 'disabled';
  api_key?: string;
};

const columnHelper = createColumnHelper<User>();

const columns: ColumnDef<User, any>[] = [
  columnHelper.accessor('login', {
    header: 'Username',
    cell: info => <div className="font-bold text-slate-900">{info.getValue()}</div>,
  }),
  columnHelper.accessor('role', {
    header: 'Role',
    cell: info => (
      <div className="flex items-center gap-1.5 text-xs font-medium text-slate-600">
        <Shield className="w-3.5 h-3.5 text-blue-500" />
        {info.getValue()}
      </div>
    ),
  }),
  columnHelper.accessor('state', {
    header: 'Status',
    cell: info => (
      <Badge variant="outline" className={`text-[10px] font-bold uppercase ${info.getValue() === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-slate-100 text-slate-500 border-slate-200'}`}>
        {info.getValue()}
      </Badge>
    ),
  }),
  columnHelper.accessor('id', {
    id: 'actions',
    header: () => <div className="text-center">Actions</div>,
    cell: ({ row }) => <UserActions user={row.original} />,
  }),
];

function UserActions({ user }: { user: User }) {
  const queryClient = useQueryClient();
  const deleteMutation = useMutation({
    mutationFn: () => api.delete(`/users/${user.id}`),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  if (user.login === 'admin') return null;

  return (
    <div className="flex justify-center">
      <button
        onClick={() => { if(confirm('Delete user?')) deleteMutation.mutate() }}
        disabled={deleteMutation.isPending}
        className="p-1.5 text-slate-400 hover:text-rose-600 transition-colors hover:bg-rose-50 rounded disabled:opacity-50"
      >
        {deleteMutation.isPending ? <Loader2 className="w-3.5 h-3.5 animate-spin" /> : <Trash2 className="w-3.5 h-3.5" />}
      </button>
    </div>
  );
}

export function Users() {
  const queryClient = useQueryClient();
  const { data, isLoading } = useQuery({
    queryKey: ['users'],
    queryFn: async () => {
      const res = await api.get('/users');
      return res.data || [];
    }
  });

  const createMutation = useMutation({
    mutationFn: (username: string) => api.post('/users', { login: username, password: 'password123', role: 'user', state: 'active' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['users'] }),
  });

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <PageHeader
        title="Administrative Users"
        description="Manage access to the SkyPlix control center."
        icon={UsersIcon}
        onAdd={() => {
          const name = prompt('Enter username:');
          if (name) createMutation.mutate(name);
        }}
        addLabel="Create User"
      />

      <DataTable
        columns={columns}
        data={data || []}
        isLoading={isLoading}
        emptyMessage="No additional users configured."
      />
    </div>
  );
}
