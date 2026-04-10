import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

const offerSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  url: z.string().url('Invalid URL'),
  state: z.enum(['active', 'disabled']),
  payout: z.number().min(0),
  affiliate_network_id: z.string().optional().nullable(),
});

type OfferFormValues = {
  name: string;
  url: string;
  state: 'active' | 'disabled';
  payout: number;
  affiliate_network_id?: string | null;
};

export function OfferEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: offer, isLoading } = useQuery({
    queryKey: ['offer', id],
    queryFn: async () => {
      if (!id || id === 'new') return null;
      const res = await api.get(`/offers/${id}`);
      return res.data;
    },
    enabled: !!id && id !== 'new',
  });

  const { data: networks } = useQuery({
    queryKey: ['networks'],
    queryFn: async () => {
      const res = await api.get('/affiliate_networks');
      return res.data || [];
    }
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm<OfferFormValues>({
    resolver: zodResolver(offerSchema),
    defaultValues: { name: '', url: '', state: 'active', payout: 0 }
  });

  useEffect(() => {
    if (offer) {
      reset({
        name: offer.name,
        url: offer.url,
        state: offer.state,
        payout: offer.payout,
        affiliate_network_id: offer.affiliate_network_id,
      });
    }
  }, [offer, reset]);

  const saveMutation = useMutation({
    mutationFn: async (data: OfferFormValues) => {
      if (id && id !== 'new') {
        return api.put(`/offers/${id}`, data);
      }
      return api.post('/offers', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['offers'] });
      navigate('/offers');
    },
  });

  const onSubmit = (data: OfferFormValues) => {
    saveMutation.mutate(data);
  };

  if (isLoading) return <div className="p-12 text-center text-slate-500 font-medium">Loading offer data...</div>;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button onClick={() => navigate('/offers')} className="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{id === 'new' ? 'Create' : 'Edit'} Offer</h1>
        </div>
        <button onClick={handleSubmit(onSubmit)} className="flex items-center space-x-2 bg-[#2563eb] text-white px-6 py-2 rounded font-bold shadow-sm hover:bg-blue-700 transition-all active:scale-95">
          <Save className="w-4 h-4" />
          <span>{saveMutation.isPending ? 'Saving...' : 'Save Offer'}</span>
        </button>
      </div>

      <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow max-w-3xl">
        <form className="space-y-6">
          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Offer Name</label>
            <input
              {...register('name')}
              placeholder="e.g., LeadGen Finance US"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none"
            />
            {errors.name && <p className="text-xs text-rose-500 font-bold">{errors.name.message}</p>}
          </div>

          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Destination URL</label>
            <input
              {...register('url')}
              placeholder="https://network.com/click?offer_id=123&subid={click_id}"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white font-mono text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all outline-none"
            />
            <p className="text-[10px] text-slate-400 font-medium italic">Use {"{click_id}"} to pass the tracking token to the network.</p>
            {errors.url && <p className="text-xs text-rose-500 font-bold">{errors.url.message}</p>}
          </div>

          <div className="grid grid-cols-2 gap-6">
            <div className="space-y-2">
              <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Affiliate Network</label>
              <select {...register('affiliate_network_id')} className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none appearance-none">
                <option value="">Direct / No Network</option>
                {networks?.map((n: any) => (
                  <option key={n.id} value={n.id}>{n.name}</option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Default Payout ($)</label>
              <input
                type="number"
                step="0.01"
                {...register('payout', { valueAsNumber: true })}
                className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none"
              />
            </div>
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
    </div>
  );
}
