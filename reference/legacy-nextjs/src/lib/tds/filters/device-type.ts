/**
 * Device Type Filter
 * Filters by device type: desktop, mobile, tablet
 */

import type { FilterInterface, StreamFilter, FilterResult } from './types';
import type { RawClick } from '../pipeline/types';

export class DeviceTypeFilter implements FilterInterface {
  name = 'device_type';
  description = 'Filter by device type (desktop, mobile, tablet)';

  process(filter: StreamFilter, rawClick: RawClick): FilterResult {
    const payload = filter.payload as { deviceTypes?: string[] };
    const deviceTypes = (payload.deviceTypes || []).map((d: string) => d.toLowerCase());
    const clickDeviceType = (rawClick.deviceType || '').toLowerCase();

    if (!clickDeviceType) {
      return {
        passed: false,
        reason: 'Device type not resolved'
      };
    }

    const matched = deviceTypes.includes(clickDeviceType);

    return {
      passed: matched,
      reason: matched 
        ? `Device type ${clickDeviceType} is in allowed list` 
        : `Device type ${clickDeviceType} is not in allowed list`,
      matchedValue: matched ? clickDeviceType : undefined
    };
  }
}
