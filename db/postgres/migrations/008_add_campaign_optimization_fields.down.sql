ALTER TABLE campaigns DROP COLUMN IF EXISTS optimization_period_hours;
ALTER TABLE campaigns DROP COLUMN IF EXISTS optimization_metric;
ALTER TABLE campaigns DROP COLUMN IF EXISTS is_optimization_enabled;
