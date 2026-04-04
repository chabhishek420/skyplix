import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { 
  createColumnHelper,
  flexRender,
  getCoreRowModel,
  useReactTable,
} from '@tanstack/react-table';
import { Megaphone, Plus, Link as LinkIcon, Trash2, Copy } from 'lucide-react';

type Campaign = {
  id: string;
  name: string;
  state: 'active' | 'disabled';
  cost_value?: number;
};

const columnHelper = createColumnHelper<Campaign>();

const columns = [
  columnHelper.accessor('name', {
    header: 'Campaign Name',
    cell: info => <div className="font-medium">{info.getValue()}</div>,
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
    cell: () => (
      <div className="flex justify-end space-x-2">
        <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/10 rounded-md" title="Copy URL">
          <LinkIcon className="w-4 h-4" />
        </button>
        <button className="p-2 text-muted-foreground hover:text-primary transition-colors hover:bg-primary/10 rounded-md" title="Clone">
          <Copy className="w-4 h-4" />
        </button>
        <button className="p-2 text-muted-foreground hover:text-destructive transition-colors hover:bg-destructive/10 rounded-md" title="Delete">
          <Trash2 className="w-4 h-4" />
        </button>
      </div>
    ),
  }),
];

export function Campaigns() {
  const { data, isLoading } = useQuery({
    queryKey: ['campaigns'],
    queryFn: async () => {
      const res = await api.get('/campaigns');
      return res.data as Campaign[];
    }
  });

  const tableData = data || [];

  const table = useReactTable({
    data: tableData,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex justify-between items-center bg-card p-6 border border-border rounded-xl shadow-sm">
        <div className="flex items-center space-x-3">
          <div className="p-3 bg-primary/10 rounded-lg">
            <Megaphone className="w-6 h-6 text-primary" />
          </div>
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-foreground">Campaigns</h1>
            <p className="text-muted-foreground text-sm">Manage tracking endpoints and dynamic routing layers.</p>
          </div>
        </div>
        <button className="flex items-center space-x-2 bg-primary text-primary-foreground px-4 py-2 rounded-lg font-medium shadow hover:opacity-90 transition-opacity">
          <Plus className="w-4 h-4" />
          <span>Create Campaign</span>
        </button>
      </div>

      <div className="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-muted-foreground">Loading campaigns...</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-[13px] text-left">
              <thead className="bg-muted text-muted-foreground border-b border-border">
                {table.getHeaderGroups().map(headerGroup => (
                  <tr key={headerGroup.id}>
                    {headerGroup.headers.map(header => (
                      <th key={header.id} className="px-4 py-2.5 font-medium whitespace-nowrap">
                        {header.isPlaceholder
                          ? null
                          : flexRender(
                              header.column.columnDef.header,
                              header.getContext()
                            )}
                      </th>
                    ))}
                  </tr>
                ))}
              </thead>
              <tbody className="divide-y divide-border">
                {table.getRowModel().rows.map(row => (
                  <tr key={row.id} className="even:bg-slate-50/50 hover:bg-muted/50 transition-colors group">
                    {row.getVisibleCells().map(cell => (
                      <td key={cell.id} className="px-4 py-2 whitespace-nowrap">
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </td>
                    ))}
                  </tr>
                ))}
                {tableData.length === 0 && (
                  <tr>
                    <td colSpan={columns.length} className="px-4 py-8 text-center text-muted-foreground">
                      No campaigns found. Create your first campaign to get started.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
