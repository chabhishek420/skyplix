/**
 * Conversion-specific Macros (extensions)
 * Based on Keitaro's Predefined macros:
 *   - OriginalStatus.php    → {original_status}
 *   - ConversionCost.php    → {conversion_cost}
 *   - ConversionProfit.php  → {conversion_profit}
 *   - ConversionRevenue.php → {conversion_revenue}
 *   - ConversionTime.php    → {conversion_time}
 *   - AnyClickMacro.php     → {any_click}
 *   - AnyConversionMacro.php → {any_conversion}
 */

import type { MacroInterface, MacroContext } from '../types';

// ---------------------------------------------------------------------------
// OriginalStatus
// PHP: AbstractConversionMacro — $conversion->get("original_status")
// ---------------------------------------------------------------------------
export class OriginalStatusMacro implements MacroInterface {
  name = 'original_status';
  description = 'Conversion original status as received from postback';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    return context.conversion?.status || null;
  }
}

// ---------------------------------------------------------------------------
// ConversionCost
// PHP: AbsConversionMacro — cost at time of conversion (not click cost)
// ---------------------------------------------------------------------------
export class ConversionCostMacro implements MacroInterface {
  name = 'conversion_cost';
  description = 'Cost recorded at time of conversion';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.cost) return '0.00';
    return context.rawClick.cost.toFixed(2);
  }
}

// ---------------------------------------------------------------------------
// ConversionProfit
// PHP: revenue - cost at conversion time
// ---------------------------------------------------------------------------
export class ConversionProfitMacro implements MacroInterface {
  name = 'conversion_profit';
  description = 'Profit at conversion time (revenue - cost)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick) return '0.00';
    const revenue = context.conversion?.revenue ?? 0;
    const cost = context.rawClick.cost ?? 0;
    return (revenue - cost).toFixed(2);
  }
}

// ---------------------------------------------------------------------------
// ConversionRevenue
// PHP: revenue recorded in conversion postback
// ---------------------------------------------------------------------------
export class ConversionRevenueMacro implements MacroInterface {
  name = 'conversion_revenue';
  description = 'Revenue recorded from conversion postback';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.conversion) return '0.00';
    return (context.conversion.revenue ?? 0).toFixed(2);
  }
}

// ---------------------------------------------------------------------------
// ConversionTime
// PHP: $conversion->getCreatedAt() formatted as datetime string
// ---------------------------------------------------------------------------
export class ConversionTimeMacro implements MacroInterface {
  name = 'conversion_time';
  description = 'Datetime of conversion in ISO 8601 format';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.conversion?.createdAt) return null;
    return context.conversion.createdAt.toISOString();
  }
}

// ---------------------------------------------------------------------------
// AnyClick — wildcard click param macro
// PHP: AnyClickMacro — returns any named param from the raw click
// Usage: {any_click:param_name}
// ---------------------------------------------------------------------------
export class AnyClickMacro implements MacroInterface {
  name = 'any_click';
  description = 'Return any named field from the raw click. Usage: {any_click:field_name}';
  alwaysRaw = false;

  process(context: MacroContext, fieldName?: string): string | null {
    if (!fieldName || !context.rawClick) return null;
    const click = context.rawClick as unknown as Record<string, unknown>;
    const val = click[fieldName];
    return val != null ? String(val) : null;
  }
}

// ---------------------------------------------------------------------------
// AnyConversion — wildcard conversion param macro
// PHP: AnyConversionMacro — returns any named param from the conversion
// Usage: {any_conversion:param_name}
// ---------------------------------------------------------------------------
export class AnyConversionMacro implements MacroInterface {
  name = 'any_conversion';
  description = 'Return any named field from the conversion data. Usage: {any_conversion:field_name}';
  alwaysRaw = false;

  process(context: MacroContext, fieldName?: string): string | null {
    if (!fieldName || !context.conversion) return null;
    const conv = context.conversion as unknown as Record<string, unknown>;
    const val = conv[fieldName];
    return val != null ? String(val) : null;
  }
}
