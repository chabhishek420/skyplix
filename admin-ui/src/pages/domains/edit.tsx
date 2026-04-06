import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft } from 'lucide-react';
import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

const domainSchema = z.object({
  domain: z.string().min(3, 'Domain must be at least 3 characters'),
  state: z.enum(['active', 'disabled']),
  campaign_id: z.string().optional().nullable(),
});

type DomainFormValues = z.infer<typeof domainSchema>;

export function DomainEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: domain, isLoading } = useQuery({
    queryKey: ['domain', id],
    queryFn: async () => {
      if (!id || id === 'new') return null;
      const res = await api.get(`/domains/${id}`);
      return res.data;
    },
    enabled: !!id && id !== 'new',
  });

  const { data: campaigns } = useQuery({
    queryKey: ['campaigns-list'],
    queryFn: async () => {
      const res = await api.get('/campaigns');
      return res.data || [];
    }
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm<DomainFormValues>({
    resolver: zodResolver(domainSchema),
    defaultValues: { state: 'active' }
  });

  useEffect(() => {
    if (domain) {
      reset({
        domain: domain.domain,
        state: domain.state,
        campaign_id: domain.campaign_id,
      });
    }
  }, [domain, reset]);

  const saveMutation = useMutation({
    mutationFn: async (data: DomainFormValues) => {
      if (id && id !== 'new') {
        return api.put(`/domains/${id}`, data);
      }
      return api.post('/domains', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['domains'] });
      navigate('/domains');
    },
  });

  const onSubmit = (data: DomainFormValues) => {
    saveMutation.mutate(data);
  };

  if (isLoading) return <div className="p-12 text-center text-slate-500 font-medium">Loading domain data...</div>;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button onClick={() => navigate('/domains')} className="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{id === 'new' ? 'Add' : 'Edit'} Domain</h1>
        </div>
        <button onClick={handleSubmit(onSubmit)} className="flex items-center space-x-2 bg-[#2563eb] text-white px-6 py-2 rounded font-bold shadow-sm hover:bg-blue-700 transition-all active:scale-95">
          <Save className="w-4 h-4" />
          <span>{saveMutation.isPending ? 'Saving...' : 'Save Domain'}</span>
        </button>
      </div>

      <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow max-w-3xl space-y-6">
        <form className="space-y-6">
          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Domain Name / Hostname</label>
            <input
              {...register('domain')}
              placeholder="e.g., track.example.com"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none"
            />
            {errors.domain && <p className="text-xs text-rose-500 font-bold">{errors.domain.message}</p>}
          </div>

          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Attach to Campaign</label>
            <select {...register('campaign_id')} className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none appearance-none">
              <option value="">Direct Access / Global (No specific campaign)</option>
              {campaigns?.map((c: any) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
            <p className="text-[10px] text-slate-400 font-medium italic">When visitors access this domain, they will be routed to the selected campaign.</p>
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
