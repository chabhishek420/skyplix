/**
 * Conversion-related Macros
 * Based on Keitaro's macro system
 */

import type { MacroInterface, MacroContext } from '../types';

/**
 * Visitor Code Macro
 * Returns visitor code for session tracking
 */
export class VisitorCodeMacro implements MacroInterface {
  name = 'visitor_code';
  description = 'Visitor code for session tracking';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.visitorCode || null;
  }
}

/**
 * Profit Macro
 * Returns profit value (revenue - cost)
 */
export class ProfitMacro implements MacroInterface {
  name = 'profit';
  description = 'Profit value (revenue - cost)';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    const revenue = (context.rawClick.saleRevenue || 0) + (context.rawClick.leadRevenue || 0);
    const cost = context.rawClick.cost || 0;
    const profit = revenue - cost;
    return profit.toFixed(2);
  }
}

/**
 * Revenue Macro
 * Returns total revenue
 */
export class RevenueMacro implements MacroInterface {
  name = 'revenue';
  description = 'Total revenue from conversions';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    const revenue = (context.rawClick.saleRevenue || 0) + (context.rawClick.leadRevenue || 0);
    return revenue.toFixed(2);
  }
}

/**
 * Sale Revenue Macro
 * Returns sale revenue
 */
export class SaleRevenueMacro implements MacroInterface {
  name = 'sale_revenue';
  description = 'Sale revenue';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    return (context.rawClick.saleRevenue || 0).toFixed(2);
  }
}

/**
 * Lead Revenue Macro
 * Returns lead revenue
 */
export class LeadRevenueMacro implements MacroInterface {
  name = 'lead_revenue';
  description = 'Lead revenue';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    return (context.rawClick.leadRevenue || 0).toFixed(2);
  }
}

/**
 * Currency Macro
 * Returns currency code
 */
export class CurrencyMacro implements MacroInterface {
  name = 'currency';
  description = 'Currency code (USD, EUR, etc.)';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.offer?.payoutCurrency || 'USD';
  }
}

/**
 * Status Macro
 * Returns conversion status
 */
export class StatusMacro implements MacroInterface {
  name = 'status';
  description = 'Conversion status';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    if (context.rawClick.isSale) return 'sale';
    if (context.rawClick.isLead) return 'lead';
    if (context.rawClick.isRejected) return 'rejected';
    return 'pending';
  }
}

/**
 * TID Macro (Transaction ID)
 * Returns transaction ID from conversion
 */
export class TidMacro implements MacroInterface {
  name = 'tid';
  description = 'Transaction ID';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.externalId || null;
  }
}

/**
 * Transaction ID Macro (alias)
 */
export class TransactionIdMacro implements MacroInterface {
  name = 'transaction_id';
  description = 'Transaction ID (alias for tid)';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return context.rawClick?.externalId || null;
  }
}

/**
 * Payout Macro
 * Returns payout amount
 */
export class PayoutMacro implements MacroInterface {
  name = 'payout';
  description = 'Payout amount';
  alwaysRaw = false;
  
  process(context: MacroContext): string | null {
    return (context.offer?.payout || 0).toFixed(2);
  }
}

/**
 * Goal Macros (1-4)
 */
export class Goal1Macro implements MacroInterface {
  name = 'goal1';
  description = 'Goal 1 status';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.goal1 ? '1' : '0';
  }
}

export class Goal2Macro implements MacroInterface {
  name = 'goal2';
  description = 'Goal 2 status';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.goal2 ? '1' : '0';
  }
}

export class Goal3Macro implements MacroInterface {
  name = 'goal3';
  description = 'Goal 3 status';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.goal3 ? '1' : '0';
  }
}

export class Goal4Macro implements MacroInterface {
  name = 'goal4';
  description = 'Goal 4 status';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.goal4 ? '1' : '0';
  }
}

/**
 * Is Lead Macro
 */
export class IsLeadMacro implements MacroInterface {
  name = 'is_lead';
  description = 'Is lead conversion';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.isLead ? '1' : '0';
  }
}

/**
 * Is Sale Macro
 */
export class IsSaleMacro implements MacroInterface {
  name = 'is_sale';
  description = 'Is sale conversion';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.isSale ? '1' : '0';
  }
}

/**
 * Is Rejected Macro
 */
export class IsRejectedMacro implements MacroInterface {
  name = 'is_rejected';
  description = 'Is rejected conversion';
  alwaysRaw = false;
  process(context: MacroContext): string | null {
    return context.rawClick?.isRejected ? '1' : '0';
  }
}
