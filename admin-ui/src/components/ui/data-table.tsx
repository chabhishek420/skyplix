import { 
  flexRender,
  getCoreRowModel,
  useReactTable,
  type ColumnDef,
} from '@tanstack/react-table';

interface DataTableProps<TData> {
  columns: ColumnDef<TData, any>[];
  data: TData[];
  isLoading?: boolean;
  emptyMessage?: string;
}

export function DataTable<TData>({
  columns,
  data,
  isLoading,
  emptyMessage = "No items found.",
}: DataTableProps<TData>) {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div className="bg-card border border-border rounded-xl shadow-sm overflow-hidden">
      {isLoading ? (
        <div className="p-8 text-center text-muted-foreground animate-pulse flex flex-col items-center gap-2">
          <div className="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
          <span>Loading data...</span>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="bg-muted/50 text-muted-foreground border-b border-border">
              {table.getHeaderGroups().map(headerGroup => (
                <tr key={headerGroup.id}>
                  {headerGroup.headers.map(header => (
                    <th key={header.id} className="px-6 py-4 font-medium">
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
                <tr key={row.id} className="hover:bg-muted/50 transition-colors group">
                  {row.getVisibleCells().map(cell => (
                    <td key={cell.id} className="px-6 py-4">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))}
              {data.length === 0 && (
                <tr>
                  <td colSpan={columns.length} className="px-6 py-12 text-center text-muted-foreground">
                    {emptyMessage}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
