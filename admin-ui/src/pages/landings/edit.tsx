import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

const landingSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  url: z.string().url('Invalid URL'),
  state: z.enum(['active', 'disabled']),
});

type LandingFormValues = z.infer<typeof landingSchema>;

export function LandingEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: landing, isLoading } = useQuery({
    queryKey: ['landing', id],
    queryFn: async () => {
      if (!id || id === 'new') return null;
      const res = await api.get(`/landings/${id}`);
      return res.data;
    },
    enabled: !!id && id !== 'new',
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm<LandingFormValues>({
    resolver: zodResolver(landingSchema),
    defaultValues: { state: 'active' }
  });

  useEffect(() => {
    if (landing) {
      reset({
        name: landing.name,
        url: landing.url,
        state: landing.state,
      });
    }
  }, [landing, reset]);

  const saveMutation = useMutation({
    mutationFn: async (data: LandingFormValues) => {
      if (id && id !== 'new') {
        return api.put(`/landings/${id}`, data);
      }
      return api.post('/landings', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['landings'] });
      navigate('/landings');
    },
  });

  const onSubmit = (data: LandingFormValues) => {
    saveMutation.mutate(data);
  };

  if (isLoading) return <div className="p-12 text-center text-slate-500 font-medium">Loading landing data...</div>;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button onClick={() => navigate('/landings')} className="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{id === 'new' ? 'Create' : 'Edit'} Landing Page</h1>
        </div>
        <button onClick={handleSubmit(onSubmit)} className="flex items-center space-x-2 bg-[#2563eb] text-white px-6 py-2 rounded font-bold shadow-sm hover:bg-blue-700 transition-all active:scale-95">
          <Save className="w-4 h-4" />
          <span>{saveMutation.isPending ? 'Saving...' : 'Save Landing'}</span>
        </button>
      </div>

      <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow max-w-3xl">
        <form className="space-y-6">
          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Landing Name</label>
            <input
              {...register('name')}
              placeholder="e.g., iPhone 15 Sweepstakes"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none"
            />
            {errors.name && <p className="text-xs text-rose-500 font-bold">{errors.name.message}</p>}
          </div>

          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">URL</label>
            <input
              {...register('url')}
              placeholder="https://your-lander.com/index.html?subid={click_id}"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white font-mono text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all outline-none"
            />
             <p className="text-[10px] text-slate-400 font-medium italic">Tracking is handled via the LP Token. Use {"{click_id}"} if you need the token in your code.</p>
            {errors.url && <p className="text-xs text-rose-500 font-bold">{errors.url.message}</p>}
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
