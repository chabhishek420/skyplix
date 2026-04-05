import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft, Copy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

const networkSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  state: z.enum(['active', 'disabled']),
});

type NetworkFormValues = z.infer<typeof networkSchema>;

export function NetworkEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [postbackInfo, setPostbackInfo] = useState<{ postback_url: string, macros: any[] } | null>(null);

  const { data: network, isLoading } = useQuery({
    queryKey: ['network', id],
    queryFn: async () => {
      if (!id || id === 'new') return null;
      const res = await api.get(`/affiliate_networks/${id}`);
      return res.data;
    },
    enabled: !!id && id !== 'new',
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm<NetworkFormValues>({
    resolver: zodResolver(networkSchema),
    defaultValues: { state: 'active' }
  });

  useEffect(() => {
    if (network) {
      reset({
        name: network.name,
        state: network.state,
      });
    }
  }, [network, reset]);

  const saveMutation = useMutation({
    mutationFn: async (data: NetworkFormValues) => {
      if (id && id !== 'new') {
        return api.put(`/affiliate_networks/${id}`, data);
      }
      return api.post('/affiliate_networks', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['networks'] });
      navigate('/networks');
    },
  });

  const fetchPostbackURL = async () => {
    if (!id || id === 'new') return;
    const res = await api.get(`/affiliate_networks/${id}/postback_url`);
    setPostbackInfo(res.data);
  };

  const onSubmit = (data: NetworkFormValues) => {
    saveMutation.mutate(data);
  };

  if (isLoading) return <div className="p-12 text-center text-slate-500 font-medium">Loading network data...</div>;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button onClick={() => navigate('/networks')} className="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{id === 'new' ? 'Add' : 'Edit'} Affiliate Network</h1>
        </div>
        <button onClick={handleSubmit(onSubmit)} className="flex items-center space-x-2 bg-[#2563eb] text-white px-6 py-2 rounded font-bold shadow-sm hover:bg-blue-700 transition-all active:scale-95">
          <Save className="w-4 h-4" />
          <span>{saveMutation.isPending ? 'Saving...' : 'Save Network'}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow h-fit">
          <form className="space-y-6">
            <div className="space-y-2">
              <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Network Name</label>
              <input
                {...register('name')}
                placeholder="e.g., Cake, HasOffers, Everflow"
                className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none"
              />
              {errors.name && <p className="text-xs text-rose-500 font-bold">{errors.name.message}</p>}
            </div>

            <div className="space-y-2">
              <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</label>
              <div className="flex gap-4">
                 <label className="flex items-center gap-2 cursor-pointer group">
                    <input type="radio" value="active" {...register('state')} className="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300" />
                    <span className="text-sm font-medium text-slate-700 group-hover:text-slate-900">Active</span>
                 </label>
                 <label className="flex items-center gap-2 cursor-pointer group">
                    <input type="radio" value="disabled" {...register('state')} className="w-4 h-4 text-blue-600 focus:ring-blue-500 border-slate-300" />
                    <span className="text-sm font-medium text-slate-700 group-hover:text-slate-900">Disabled</span>
                 </label>
              </div>
            </div>
          </form>
        </div>

        {id !== 'new' && (
          <div className="space-y-6">
            <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Postback Integration</h3>
                <button
                  onClick={fetchPostbackURL}
                  className="text-[11px] font-bold text-[#2563eb] hover:underline"
                >
                  Generate Link
                </button>
              </div>

              {postbackInfo ? (
                <div className="space-y-4">
                  <div className="p-3 bg-slate-50 rounded border border-slate-100 font-mono text-[11px] break-all relative group">
                    {postbackInfo.postback_url}
                    <button
                      onClick={() => navigator.clipboard.writeText(postbackInfo.postback_url)}
                      className="absolute right-2 top-2 p-1 bg-white border border-slate-200 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <Copy className="w-3 h-3 text-slate-400" />
                    </button>
                  </div>

                  <div className="space-y-2">
                    <p className="text-[10px] font-bold text-slate-400 uppercase">Available Macros</p>
                    <div className="grid grid-cols-2 gap-2">
                      {postbackInfo.macros.map((m: any) => (
                        <div key={m.name} className="flex flex-col p-2 bg-slate-50/50 rounded border border-slate-100/50">
                          <span className="text-[11px] font-bold text-slate-700 font-mono">{m.name}</span>
                          <span className="text-[9px] text-slate-400 font-medium">{m.description}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ) : (
                <p className="text-xs text-slate-400 italic">Click "Generate Link" to see the Postback URL for this network.</p>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
