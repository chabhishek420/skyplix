import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft, Layers, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '@/lib/api';
import { StreamEditor } from '@/components/campaigns/stream-editor';

const campaignSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  alias: z.string().min(2, 'Alias must be at least 2 characters'),
  state: z.enum(['active', 'disabled']),
  type: z.enum(['POSITION', 'WEIGHT']),
});

type CampaignFormValues = z.infer<typeof campaignSchema>;

export function CampaignEdit() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState<'general' | 'streams'>('general');

  const { data: campaign } = useQuery({
    queryKey: ['campaign', id],
    queryFn: async () => {
      if (id === 'new') return null;
      const res = await api.get(`/campaigns/${id}`);
      return res.data;
    },
    enabled: id !== 'new',
  });

  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(campaignSchema),
    values: campaign || { state: 'active', type: 'POSITION' }
  });

  const saveMutation = useMutation({
    mutationFn: async (data: CampaignFormValues) => {
      if (id === 'new') {
        return api.post('/campaigns', data);
      }
      return api.put(`/campaigns/${id}`, data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['campaigns'] });
      navigate('/campaigns');
    }
  });

  const onSubmit = (data: CampaignFormValues) => {
    saveMutation.mutate(data);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button
            onClick={() => navigate('/campaigns')}
            className="p-2 text-muted-foreground hover:bg-muted hover:text-foreground rounded-full transition-colors"
          >
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-3xl font-bold tracking-tight text-foreground">
            {id === 'new' ? 'Create Campaign' : 'Edit Campaign'}
          </h1>
        </div>
        <button
          onClick={handleSubmit(onSubmit)}
          disabled={saveMutation.isPending}
          className="flex items-center space-x-2 bg-primary text-primary-foreground px-6 py-2.5 rounded-lg font-medium shadow-md hover:opacity-90 transition-opacity disabled:opacity-50"
        >
          {saveMutation.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Save className="w-4 h-4" />}
          <span>{id === 'new' ? 'Create Campaign' : 'Save Changes'}</span>
        </button>
      </div>

      <div className="flex space-x-1 border-b border-border">
        <button
          onClick={() => setActiveTab('general')}
          className={`px-4 py-3 text-sm font-medium border-b-2 transition-colors ${activeTab === 'general' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
        >
          General Settings
        </button>
        <button
          onClick={() => setActiveTab('streams')}
          className={`flex items-center space-x-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors ${activeTab === 'streams' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'}`}
        >
          <Layers className="w-4 h-4" />
          <span>Streams Engine</span>
        </button>
      </div>

      {activeTab === 'general' ? (
        <div className="bg-card border border-border p-8 rounded-xl shadow-sm max-w-3xl">
          <form className="space-y-6">
            <div className="grid grid-cols-2 gap-6">
              <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">Campaign Name</label>
                <input
                  {...register('name')}
                  placeholder="e.g., US sweepstakes traffic"
                  className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                />
                {errors.name && <p className="text-sm text-destructive">{errors.name.message as string}</p>}
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">Alias</label>
                <input
                  {...register('alias')}
                  placeholder="e.g., us-sweep"
                  className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
                />
                {errors.alias && <p className="text-sm text-destructive">{errors.alias.message as string}</p>}
              </div>
            </div>

            <div className="grid grid-cols-2 gap-6">
              <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">State</label>
                <select {...register('state')} className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                  <option value="active">Active</option>
                  <option value="disabled">Disabled</option>
                </select>
              </div>

              <div className="space-y-2">
                <label className="text-sm font-medium text-foreground">Rotation Type</label>
                <select {...register('type')} className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                  <option value="POSITION">Position (Sequential)</option>
                  <option value="WEIGHT">Weight (Random)</option>
                </select>
              </div>
            </div>
          </form>
        </div>
      ) : (
        <StreamEditor />
      )}
    </div>
  );
}
