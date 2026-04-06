import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';

type LogResponse = {
  rows: Array<Record<string, unknown>>;
  limit: number;
  offset: number;
  total: number;
};

export function ConversionsLog() {
  const { data, isLoading, error, refetch } = useQuery<LogResponse>({
    queryKey: ['logs', 'conversions'],
    queryFn: async () => {
      const resp = await api.get('/logs/conversions', {
        params: {
          preset: 'today',
          limit: 100,
          offset: 0,
        },
      });
      return resp.data as LogResponse;
    },
  });

  const rows = data?.rows ?? [];
  const columns = rows.length > 0 ? Object.keys(rows[0]) : [];

  if (isLoading) {
    return <div className="p-6 text-sm text-muted-foreground">Loading conversions…</div>;
  }

  if (error) {
    return (
      <div className="p-6">
        <div className="text-sm text-destructive">Failed to load conversions.</div>
        <button
          className="mt-4 rounded-md bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground"
          onClick={() => refetch()}
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-xl font-semibold">Conversions Log</h1>
        <button
          className="rounded-md bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground"
          onClick={() => refetch()}
        >
          Refresh
        </button>
      </div>

      {rows.length === 0 ? (
        <div className="text-sm text-muted-foreground">No conversions found.</div>
      ) : (
        <div className="overflow-auto rounded-lg border border-border">
          <table className="min-w-full text-sm">
            <thead className="bg-muted/50">
              <tr>
                {columns.map((c) => (
                  <th key={c} className="whitespace-nowrap px-3 py-2 text-left font-semibold">
                    {c}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {rows.map((r, idx) => (
                <tr key={idx} className="border-t border-border">
                  {columns.map((c) => (
                    <td key={c} className="whitespace-nowrap px-3 py-2">
                      {String(r[c] ?? '')}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
