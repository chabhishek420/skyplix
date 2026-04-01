/**
 * UpdateStreamUniquenessSessionStage
 * 
 * Checks and updates stream-level uniqueness.
 * Based on Keitaro's UpdateStreamUniquenessSessionStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { cookiesService } from '../../services/cookies-service';


export class UpdateStreamUniquenessSessionStage implements StageInterface {
  name = 'UpdateStreamUniquenessSessionStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick || !payload.stream) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const stream = payload.stream;

    // Check uniqueness based on visitor code and stream
    if (payload.request && rawClick.visitorCode) {
      const isUnique = cookiesService.isUniqueForStream(payload.request, rawClick.visitorCode, stream.id.toString());
      rawClick.isUniqueStream = isUnique;
      
      if (!isUnique) {
        payload.log(`Stream uniqueness check: NOT unique (visitor already has cookie for stream ${stream.id})`);
      } else {
        payload.log(`Stream uniqueness check: UNIQUE (stream: ${stream.id})`);
      }
    } else {
      // Default to unique
      rawClick.isUniqueStream = true;
    }


    return {
      success: true,
      payload
    };
  }
}
