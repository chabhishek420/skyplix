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
    <div className="flex justify-between items-center bg-card p-6 border border-border rounded-xl shadow-sm">
      <div className="flex items-center space-x-3">
        <div className="p-3 bg-primary/10 rounded-lg">
          <Icon className="w-6 h-6 text-primary" />
        </div>
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground">{title}</h1>
          <p className="text-muted-foreground text-sm">{description}</p>
        </div>
      </div>
      <button 
        onClick={onAdd}
        className="flex items-center space-x-2 bg-primary text-primary-foreground px-4 py-2 rounded-lg font-medium shadow hover:opacity-90 transition-opacity"
      >
        <Plus className="w-4 h-4" />
        <span>{addLabel}</span>
      </button>
    </div>
  );
}
