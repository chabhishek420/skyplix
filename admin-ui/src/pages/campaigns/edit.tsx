import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { Save, ArrowLeft, Layers } from 'lucide-react';
import { useState } from 'react';
import { StreamEditor } from '@/components/campaigns/stream-editor';

const campaignSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  state: z.enum(['active', 'disabled']),
  is_optimization_enabled: z.boolean().default(false),
  cost_type: z.enum(['cpa', 'cpc', 'revshare']),
  cost_value: z.coerce.number().min(0).optional(),
});

type CampaignFormValues = z.infer<typeof campaignSchema>;

export function CampaignEdit() {
  const [activeTab, setActiveTab] = useState<'general' | 'streams'>('general');

  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(campaignSchema),
    defaultValues: { state: 'active', cost_type: 'cpa' }
  });

  const onSubmit = (data: CampaignFormValues) => {
    console.log('Save Campaign', data);
    // API Call here
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <button className="p-2 text-muted-foreground hover:bg-muted hover:text-foreground rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </button>
          <h1 className="text-3xl font-bold tracking-tight text-foreground">Edit Campaign</h1>
        </div>
        <button onClick={handleSubmit(onSubmit)} className="flex items-center space-x-2 bg-primary text-primary-foreground px-6 py-2.5 rounded-lg font-medium shadow-md hover:opacity-90 transition-opacity">
          <Save className="w-4 h-4" />
          <span>Save Changes</span>
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
            <div className="space-y-2">
              <label className="text-sm font-medium text-foreground">Campaign Name</label>
              <input 
                {...register('name')}
                placeholder="e.g., US sweepstakes traffic"
                className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
              />
              {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
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
                <label className="text-sm font-medium text-foreground">Cost Type</label>
                <select {...register('cost_type')} className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all">
                  <option value="cpa">CPA (Cost Per Action)</option>
                  <option value="cpc">CPC (Cost Per Click)</option>
                  <option value="revshare">RevShare</option>
                </select>
              </div>
            </div>
            
            <div className="space-y-2">
              <label className="text-sm font-medium text-foreground">Cost Value ($)</label>
              <input 
                type="number"
                step="0.01"
                {...register('cost_value')}
                className="w-full px-4 py-2.5 rounded-lg border border-border bg-background focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
              />
            </div>

            <div className="flex items-center space-x-2 pt-4">
              <input
                type="checkbox"
                id="is_optimization_enabled"
                {...register('is_optimization_enabled')}
                className="w-4 h-4 text-primary border-border rounded focus:ring-primary"
              />
              <label htmlFor="is_optimization_enabled" className="text-sm font-semibold text-foreground">
                Enable MAB Auto-Optimization
              </label>
              <span className="text-[11px] text-muted-foreground italic">(Automatic weight adjustment based on CR/EPC)</span>
            </div>
          </form>
        </div>
      ) : (
        <StreamEditor />
      )}
    </div>
  );
}
