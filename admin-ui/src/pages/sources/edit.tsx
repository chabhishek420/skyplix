import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft, Trash2, Plus } from 'lucide-react';
import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';

const sourceSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  state: z.enum(['active', 'disabled']),
  postback_url: z.string().optional().nullable(),
  param_list: z.array(z.object({
    key: z.string(),
    placeholder: z.string(),
  })),
});

type SourceFormValues = {
  name: string;
  state: 'active' | 'disabled';
  postback_url?: string | null;
  param_list: { key: string, placeholder: string }[];
};

export function SourceEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: source, isLoading } = useQuery({
    queryKey: ['source', id],
    queryFn: async () => {
      if (!id || id === 'new') return null;
      const res = await api.get(`/traffic_sources/${id}`);
      return res.data;
    },
    enabled: !!id && id !== 'new',
  });

  const { register, handleSubmit, reset, control, formState: { errors } } = useForm<SourceFormValues>({
    resolver: zodResolver(sourceSchema),
    defaultValues: {
      name: '',
      state: 'active',
      param_list: [{ key: 'sub1', placeholder: '{subid}' }]
    }
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: "param_list"
  });

  useEffect(() => {
    if (source) {
      const paramList = Object.entries(source.params || {}).map(([key, placeholder]) => ({
        key,
        placeholder: String(placeholder),
      }));

      reset({
        name: source.name,
        state: source.state,
        postback_url: source.postback_url,
        param_list: paramList.length > 0 ? paramList : [{ key: '', placeholder: '' }],
      });
    }
  }, [source, reset]);

  const saveMutation = useMutation({
    mutationFn: async (values: SourceFormValues) => {
      // Convert list back to record
      const params: Record<string, string> = {};
      values.param_list.forEach(p => {
        if (p.key) params[p.key] = p.placeholder;
      });

      const data = {
        name: values.name,
        state: values.state,
        postback_url: values.postback_url,
        params,
      };

      if (id && id !== 'new') {
        return api.put(`/traffic_sources/${id}`, data);
      }
      return api.post('/traffic_sources', data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['sources'] });
      navigate('/sources');
    },
  });

  const onSubmit = (data: SourceFormValues) => {
    saveMutation.mutate(data);
  };

  if (isLoading) return <div className="p-12 text-center text-slate-500 font-medium">Loading source data...</div>;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button onClick={() => navigate('/sources')} className="p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900 rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{id === 'new' ? 'Add' : 'Edit'} Traffic Source</h1>
        </div>
        <button onClick={handleSubmit(onSubmit)} className="flex items-center space-x-2 bg-[#2563eb] text-white px-6 py-2 rounded font-bold shadow-sm hover:bg-blue-700 transition-all active:scale-95">
          <Save className="w-4 h-4" />
          <span>{saveMutation.isPending ? 'Saving...' : 'Save Source'}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow h-fit space-y-6">
          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Source Name</label>
            <input
              {...register('name')}
              placeholder="e.g., Facebook Ads, TikTok, Google Search"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all text-sm outline-none"
            />
            {errors.name && <p className="text-xs text-rose-500 font-bold">{errors.name.message}</p>}
          </div>

          <div className="space-y-2">
            <label className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">S2S Postback URL (Optional)</label>
            <input
              {...register('postback_url')}
              placeholder="https://source.com/pixel?click_id={external_id}&payout={payout}"
              className="w-full px-4 py-2 rounded border border-slate-200 bg-white font-mono text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all outline-none"
            />
            <p className="text-[10px] text-slate-400 font-medium italic">SkyPlix will fire this URL when a conversion occurs.</p>
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
        </div>

        <div className="bg-white border border-[#e2e8f0] p-8 rounded whisper-shadow">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-[11px] font-bold text-slate-400 uppercase tracking-widest">URL Parameters</h3>
            <button
              type="button"
              onClick={() => append({ key: '', placeholder: '' })}
              className="flex items-center space-x-1 text-[11px] font-bold text-[#2563eb] hover:bg-blue-50 px-2 py-1 rounded transition-colors"
            >
              <Plus className="w-3 h-3" />
              <span>Add Parameter</span>
            </button>
          </div>

          <div className="space-y-3">
            {fields.map((field, index) => (
              <div key={field.id} className="flex gap-3 items-start animate-in slide-in-from-right-2 duration-200">
                <div className="flex-1 space-y-1">
                  <input
                    {...register(`param_list.${index}.key` as const)}
                    placeholder="URL Parameter (e.g., sub1)"
                    className="w-full px-3 py-1.5 rounded border border-slate-200 bg-white font-mono text-[11px] outline-none focus:border-blue-400"
                  />
                </div>
                <div className="flex-1 space-y-1">
                  <input
                    {...register(`param_list.${index}.placeholder` as const)}
                    placeholder="Source Macro (e.g., {subid})"
                    className="w-full px-3 py-1.5 rounded border border-slate-200 bg-white font-mono text-[11px] outline-none focus:border-blue-400"
                  />
                </div>
                <button
                  type="button"
                  onClick={() => remove(index)}
                  className="p-2 text-slate-300 hover:text-rose-500 transition-colors"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                </button>
              </div>
            ))}
            {fields.length === 0 && (
              <p className="text-xs text-slate-400 italic text-center py-4">No parameters configured. Incoming clicks will only use standard fields.</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
