-- Phase 8: MAB Auto-Optimization
ALTER TABLE campaigns ADD COLUMN IF NOT EXISTS is_optimization_enabled BOOLEAN NOT NULL DEFAULT false;
