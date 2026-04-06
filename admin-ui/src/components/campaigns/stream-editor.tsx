import { Plus, Filter, Trash2, GripVertical, PlayCircle, ShieldAlert } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { useParams } from 'react-router-dom';

export function StreamEditor() {
  const { id: campaignID } = useParams();

  const { data: streams, isLoading } = useQuery({
    queryKey: ['campaign-streams', campaignID],
    queryFn: async () => {
      if (!campaignID) return [];
      const res = await api.get(`/campaigns/${campaignID}/streams`);
      return res.data || [];
    },
    enabled: !!campaignID,
  });

  if (isLoading) return <div className="p-8 text-center text-slate-400">Loading traffic pipeline...</div>;

  const displayStreams = (streams && streams.length > 0) ? streams : [];

  return (
    <div className="space-y-6 animate-in slide-in-from-bottom-2 duration-300">
      <div className="flex justify-between items-center mb-6">
        <h3 className="text-xl font-bold tracking-tight text-slate-900">Traffic Pipeline</h3>
        <button className="flex items-center space-x-2 bg-blue-50 text-[#2563eb] hover:bg-[#2563eb] hover:text-white px-4 py-2 rounded-lg font-bold transition-all border border-blue-100">
          <Plus className="w-4 h-4" />
          <span>Add Stream</span>
        </button>
      </div>

      <div className="space-y-3">
        {displayStreams.map((stream: any) => (
          <div key={stream.id} className="group bg-white border border-slate-200 p-4 rounded-xl flex items-center justify-between hover:border-blue-300 transition-colors cursor-pointer whisper-shadow">
            <div className="flex items-center space-x-4">
              <div className="text-slate-300 group-hover:text-slate-400 cursor-grab">
                <GripVertical className="w-5 h-5" />
              </div>
              <div className={`w-8 h-8 rounded-full flex items-center justify-center ${stream.type === 'FORCED' ? 'bg-rose-50 text-rose-500' : 'bg-blue-50 text-blue-500'}`}>
                {stream.type === 'FORCED' ? <ShieldAlert className="w-4 h-4" /> : <PlayCircle className="w-4 h-4" />}
              </div>
              <div>
                <div className="font-bold text-slate-900 flex items-center space-x-2">
                  <span>{stream.name}</span>
                  <span className="text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full border border-slate-100">
                    Weight: {stream.weight}
                  </span>
                </div>
                <div className="text-[11px] text-slate-500 flex items-center space-x-4 mt-1 font-medium">
                  <span className="capitalize">{stream.action_type} Action</span>
                  <span className="flex items-center space-x-1">
                    <Filter className="w-3 h-3" />
                    <span>{stream.filters?.length || 0} conditions</span>
                  </span>
                </div>
              </div>
            </div>
            
            <div className="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button className="px-3 py-1.5 bg-slate-50 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors text-[11px] font-bold border border-slate-200">
                Edit Filters
              </button>
              <button className="p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors">
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        ))}
        {displayStreams.length === 0 && (
          <div className="py-12 text-center border-2 border-dashed border-slate-100 rounded-xl">
             <p className="text-slate-400 text-sm font-medium italic underline decoration-slate-100 underline-offset-4">Pipeline is empty. Traffic will hit the default landing/offer.</p>
          </div>
        )}
      </div>

      {/* Embedded Filter Designer */}
      <div className="mt-8 border border-slate-200 bg-white rounded-xl p-8 whisper-shadow">
        <h4 className="font-bold text-slate-900 mb-6 flex items-center space-x-2">
          <Filter className="w-4 h-4 text-blue-500" />
          <span className="text-sm uppercase tracking-widest">Condition Group (AND)</span>
        </h4>
        <div className="space-y-4">
          <p className="text-xs text-slate-400 italic">Select a stream above to edit its logical filters (Geo, Device, UA, etc).</p>
        </div>
      </div>
    </div>
  );
}
