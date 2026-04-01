/**
 * Stream Macros
 */

import type { MacroInterface, MacroContext } from '../types';

export class StreamIdMacro implements MacroInterface {
  name = 'stream_id';
  description = 'Stream ID';
  alwaysRaw = false;

  process(context: MacroContext): string | null {
    if (context.stream) {
      return context.stream.id;
    }
    if (context.rawClick?.streamId) {
      return context.rawClick.streamId;
    }
    return null;
  }
}
