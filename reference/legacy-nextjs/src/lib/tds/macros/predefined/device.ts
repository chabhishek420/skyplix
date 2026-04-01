/**
 * Device Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class DeviceTypeMacro implements MacroInterface {
  name = 'device_type';
  description = 'Device type (desktop, mobile, tablet)';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.deviceType) return null;
    return context.rawClick.deviceType;
  }
}

export class DeviceModelMacro implements MacroInterface {
  name = 'device_model';
  description = 'Device model';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.deviceModel) return null;
    return context.rawClick.deviceModel;
  }
}

export class DeviceBrandMacro implements MacroInterface {
  name = 'device_brand';
  description = 'Device brand';
  alwaysRaw = true;

  process(context: MacroContext): string | null {
    if (!context.rawClick?.deviceBrand) return null;
    return context.rawClick.deviceBrand;
  }
}
