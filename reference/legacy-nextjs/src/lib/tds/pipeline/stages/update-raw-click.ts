/**
 * UpdateRawClickStage
 * 
 * Updates raw click with campaign data and generates click ID.
 * Based on Keitaro's UpdateRawClickStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';

import { generateUniqueClickId } from '../../click-id';

export class UpdateRawClickStage implements StageInterface {
  name = 'UpdateRawClickStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick) {
      return {
        success: false,
        payload,
        error: 'Empty rawClick',
        abort: true
      };
    }

    const rawClick = payload.rawClick;
    const campaign = payload.campaign;
    const stream = payload.stream;

    // Generate click ID if not set (with collision detection)
    if (!rawClick.clickId) {
      rawClick.clickId = await generateUniqueClickId();
    }

    // Set campaign ID
    if (campaign) {
      rawClick.campaignId = campaign.id;
    }

    // Set stream ID
    if (stream) {
      rawClick.streamId = stream.id;
    }

    // Set datetime if not set
    if (!rawClick.datetime) {
      rawClick.datetime = new Date();
    }

    // Process sub IDs from parameters
    if (payload.request) {
      const params = payload.getAllParams();
      
      // Support both sub_id_N and subidN formats
      for (let i = 1; i <= 5; i++) {
        const subId = params[`sub_id_${i}`] || params[`subid${i}`];
        if (subId) {
          (rawClick as any)[`subId${i}`] = decodeURIComponent(subId);
        }
      }

      // Process extra params
      for (let i = 1; i <= 3; i++) {
        const extraParam = params[`extra_param_${i}`];
        if (extraParam) {
          (rawClick as any)[`extraParam${i}`] = decodeURIComponent(extraParam);
        }
      }

      // Handle Traffic Source specific parameter overrides
      if (campaign?.trafficSource) {
        const ts = campaign.trafficSource;
        
        // Override source if parameter is present
        if (ts.sourceParam && params[ts.sourceParam]) {
          rawClick.source = decodeURIComponent(params[ts.sourceParam]);
          payload.log(`Traffic source override: source=${rawClick.source} (param: ${ts.sourceParam})`);
        }
        
        // Override keyword if parameter is present
        if (ts.keywordParam && params[ts.keywordParam]) {
          rawClick.keyword = decodeURIComponent(params[ts.keywordParam]);
          payload.log(`Traffic source override: keyword=${rawClick.keyword} (param: ${ts.keywordParam})`);
        }
      }
    }

    payload.log(`Updated raw click: clickId=${rawClick.clickId}, campaignId=${rawClick.campaignId}`);

    return {
      success: true,
      payload
    };
  }
}
