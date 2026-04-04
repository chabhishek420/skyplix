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
    <div className="bg-white border border-[#e2e8f0] rounded whisper-shadow overflow-hidden">
      {isLoading ? (
        <div className="p-12 text-center text-slate-400 font-medium animate-pulse flex flex-col items-center gap-3">
          <div className="w-5 h-5 border-2 border-[#2563eb] border-t-transparent rounded-full animate-spin"></div>
          <span className="text-[13px]">Synchronizing data...</span>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead className="bg-[#fcfdfe] border-b border-slate-100">
              {table.getHeaderGroups().map(headerGroup => (
                <tr key={headerGroup.id}>
                  {headerGroup.headers.map(header => (
                    <th key={header.id} className="px-6 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">
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
            <tbody className="divide-y divide-slate-50/50">
              {table.getRowModel().rows.map((row, i) => (
                <tr key={row.id} className={`${i % 2 === 1 ? 'bg-[#fcfdfe]' : ''} hover:bg-slate-50 transition-colors`}>
                  {row.getVisibleCells().map(cell => (
                    <td key={cell.id} className="px-6 py-3.5 whitespace-nowrap border-r border-slate-50/50 last:border-0">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))}
              {data.length === 0 && (
                <tr>
                  <td colSpan={columns.length} className="px-6 py-16 text-center text-slate-400 text-[13px] font-medium">
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
