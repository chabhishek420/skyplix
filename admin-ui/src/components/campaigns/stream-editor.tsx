import { Plus, Filter, Trash2, GripVertical, PlayCircle, ShieldAlert } from 'lucide-react';

const MOCK_STREAMS = [
  { id: '1', name: 'Bot Protection Filter', type: 'intercept', filters: 2, weight: 100 },
  { id: '2', name: 'Mobile Sweeps (US Only)', type: 'redirect', filters: 3, weight: 60 },
  { id: '3', name: 'Desktop Sweeps (Default)', type: 'redirect', filters: 0, weight: 40 },
];

export function StreamEditor() {
  return (
    <div className="space-y-6 animate-in slide-in-from-bottom-2 duration-300">
      <div className="flex justify-between items-center mb-6">
        <h3 className="text-xl font-bold tracking-tight text-foreground">Traffic Pipeline</h3>
        <button className="flex items-center space-x-2 bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground px-4 py-2 rounded-lg font-medium transition-all">
          <Plus className="w-4 h-4" />
          <span>Add Stream</span>
        </button>
      </div>

      <div className="space-y-3">
        {MOCK_STREAMS.map((stream) => (
          <div key={stream.id} className="group bg-card border border-border p-4 rounded-xl flex items-center justify-between hover:border-primary/50 transition-colors cursor-pointer shadow-sm">
            <div className="flex items-center space-x-4">
              <div className="text-muted-foreground/50 group-hover:text-muted-foreground cursor-grab">
                <GripVertical className="w-5 h-5" />
              </div>
              <div className={`w-8 h-8 rounded-full flex items-center justify-center ${stream.type === 'intercept' ? 'bg-destructive/10 text-destructive' : 'bg-primary/10 text-primary'}`}>
                {stream.type === 'intercept' ? <ShieldAlert className="w-4 h-4" /> : <PlayCircle className="w-4 h-4" />}
              </div>
              <div>
                <div className="font-semibold text-foreground flex items-center space-x-2">
                  <span>{stream.name}</span>
                  <span className="text-xs text-muted-foreground bg-muted px-2 py-0.5 rounded-full">
                    Weight: {stream.weight}
                  </span>
                </div>
                <div className="text-sm text-muted-foreground flex items-center space-x-4 mt-1">
                  <span className="capitalize">{stream.type} Action</span>
                  <span className="flex items-center space-x-1">
                    <Filter className="w-3 h-3" />
                    <span>{stream.filters} conditions</span>
                  </span>
                </div>
              </div>
            </div>
            
            <div className="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button className="p-2 bg-secondary text-secondary-foreground hover:bg-muted rounded-lg transition-colors text-sm font-medium">
                Edit Filters
              </button>
              <button className="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition-colors">
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Embedded Filter Designer Mockup */}
      <div className="mt-8 border border-border bg-background rounded-xl p-6">
        <h4 className="font-semibold mb-4 flex items-center space-x-2">
          <Filter className="w-4 h-4" />
          <span>Condition Group (AND)</span>
        </h4>
        <div className="space-y-4">
          <div className="flex items-center space-x-3 bg-card p-3 rounded-lg border border-border">
            <select className="bg-muted px-3 py-1.5 rounded-md text-sm border-none">
              <option>Geo / Country</option>
              <option>Device Type</option>
              <option>Connection Type</option>
            </select>
            <select className="bg-muted px-3 py-1.5 rounded-md text-sm border-none">
              <option>IS</option>
              <option>IS NOT</option>
            </select>
            <div className="flex-1 bg-muted px-3 py-1.5 rounded-md text-sm cursor-text border border-transparent focus-within:border-primary/50">
              United States, Canada
            </div>
            <button className="p-1.5 text-muted-foreground hover:text-destructive transition-colors"><Trash2 className="w-4 h-4" /></button>
          </div>

          <div className="flex items-center space-x-3 bg-card p-3 rounded-lg border border-border">
            <select className="bg-muted px-3 py-1.5 rounded-md text-sm border-none">
              <option>Device Type</option>
            </select>
            <select className="bg-muted px-3 py-1.5 rounded-md text-sm border-none">
              <option>IS</option>
            </select>
            <div className="flex-1 bg-muted px-3 py-1.5 rounded-md text-sm cursor-text border border-transparent">
              Mobile, Tablet
            </div>
            <button className="p-1.5 text-muted-foreground hover:text-destructive transition-colors"><Trash2 className="w-4 h-4" /></button>
          </div>

          <button className="text-sm border border-dashed border-border text-muted-foreground hover:text-primary hover:border-primary/50 w-full py-3 rounded-lg transition-colors">
            + Add Condition
          </button>
        </div>
      </div>
    </div>
  );
}
