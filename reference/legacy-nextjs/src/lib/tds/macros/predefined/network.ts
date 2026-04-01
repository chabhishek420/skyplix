/**
 * Network & Request Macros
 * Based on Keitaro's Predefined macros:
 *   - ConnectionType.php  → {connection_type}
 *   - Operator.php        → {operator}
 *   - XRequestedWith.php  → {x_requested_with}
 *   - CurrentDomain.php   → {current_domain}
 *   - TrafficSourceName.php → {traffic_source_name}
 *   - Debug.php           → {debug}
 */

import type { MacroInterface, MacroContext } from '../types';

// ---------------------------------------------------------------------------
// ConnectionType
// PHP: $rawClick->getConnectionType()
// ---------------------------------------------------------------------------
export class ConnectionTypeMacro implements MacroInterface {
  name = 'connection_type';
  description = 'Connection type (e.g., broadband, mobile, dialup)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    return context.rawClick?.connectionType || null;
  }
}

// ---------------------------------------------------------------------------
// Operator
// PHP: $rawClick->getOperator()
// ---------------------------------------------------------------------------
export class OperatorMacro implements MacroInterface {
  name = 'operator';
  description = 'Mobile operator / ISP operator name';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    return context.rawClick?.operator || null;
  }
}

// ---------------------------------------------------------------------------
// XRequestedWith
// PHP: from request headers — X-Requested-With
// ---------------------------------------------------------------------------
export class XRequestedWithMacro implements MacroInterface {
  name = 'x_requested_with';
  description = 'X-Requested-With header value (e.g., XMLHttpRequest)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    return context.rawClick?.xRequestedWith || null;
  }
}

// ---------------------------------------------------------------------------
// CurrentDomain
// PHP: $uri->getScheme() . "://" . $uri->getHost()
// ---------------------------------------------------------------------------
export class CurrentDomainMacro implements MacroInterface {
  name = 'current_domain';
  description = 'Current request domain with scheme (e.g., https://example.com)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick) return null;
    // Try to extract from the request params if available
    const host = context.params?.['__host__'] || context.params?.['host'];
    if (host) return host;
    // Fallback: not available without request context
    return null;
  }
}

// ---------------------------------------------------------------------------
// TrafficSourceName
// PHP: campaign->getTrafficSource()->getName()
// ---------------------------------------------------------------------------
export class TrafficSourceNameMacro implements MacroInterface {
  name = 'traffic_source_name';
  description = 'Traffic source name attached to the campaign';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    // The campaign holds the traffic source reference; name surfaced via params
    return context.params?.['traffic_source_name'] || null;
  }
}

// ---------------------------------------------------------------------------
// Debug
// PHP: json_encode({headers, server_params, click, method, uri})
// CAUTION: only enabled in debug/dev mode
// ---------------------------------------------------------------------------
export class DebugMacro implements MacroInterface {
  name = 'debug';
  description = 'Debug dump of click data as JSON (dev environments only)';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (process.env.NODE_ENV === 'production') {
      return '[debug suppressed in production]';
    }

    const click = context.rawClick;
    if (!click) return null;

    const output = {
      click_id: click.clickId,
      campaign_id: click.campaignId,
      stream_id: click.streamId,
      ip: click.ip,
      country: click.country,
      region: click.region,
      city: click.city,
      browser: click.browser,
      os: click.os,
      device_type: click.deviceType,
      is_bot: click.isBot,
      bot_reason: click.botReason,
      user_agent: click.userAgent,
      referrer: click.referrer,
      datetime: click.datetime,
    };

    return JSON.stringify(output, null, 2);
  }
}
