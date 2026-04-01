/**
 * PrepareRawClickToStoreStage
 * 
 * Prepares raw click data for database storage.
 * Based on Keitaro's PrepareRawClickToStoreStage.php
 */

import type { StageInterface, StageResult, RawClick, PipelinePayload } from '../types';


export class PrepareRawClickToStoreStage implements StageInterface {
  name = 'PrepareRawClickToStoreStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const campaign = payload.campaign;
    const stream = payload.stream;

    // Determine if click should be stored
    let shouldStore = true;

    // Check campaign settings
    if (campaign) {
      // Don't store bot clicks if configured
      if (rawClick.isBot) {
        shouldStore = false; // Or store with bot flag
      }
    }

    // Check stream settings
    if (stream && !stream.collectClicks) {
      shouldStore = false;
    }

    if (shouldStore) {
      // Mark click as processed
      (rawClick as any).processed = true;
      
      // Add to storage queue
      payload.addRawClickToStore(rawClick);
      payload.log(`Prepared click for storage: ${rawClick.clickId}`);
    } else {
      payload.log(`Click not stored (bot=${rawClick.isBot}, collectClicks=${stream?.collectClicks})`);
    }

    return {
      success: true,
      payload
    };
  }
}
