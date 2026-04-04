import React from 'react';
import { Plus } from 'lucide-react';

interface PageHeaderProps {
  title: string;
  description: string;
  icon: React.ElementType;
  onAdd: () => void;
  addLabel: string;
}

export function PageHeader({ title, description, icon: Icon, onAdd, addLabel }: PageHeaderProps) {
  return (
    <div className="flex justify-between items-end mb-6">
      <div className="flex items-center space-x-4">
        <div className="w-12 h-12 rounded bg-white border border-slate-100 flex items-center justify-center whisper-shadow shadow-slate-100">
          <Icon className="w-6 h-6 text-[#2563eb]" />
        </div>
        <div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{title}</h1>
          <p className="text-slate-500 text-[13px] font-medium mt-0.5">{description}</p>
        </div>
      </div>
      <button 
        onClick={onAdd}
        className="flex items-center space-x-1.5 bg-[#2563eb] text-white px-5 py-2 rounded text-[13px] font-bold shadow-sm shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95"
      >
        <Plus className="w-4 h-4" />
        <span>{addLabel}</span>
      </button>
    </div>
  );
}
