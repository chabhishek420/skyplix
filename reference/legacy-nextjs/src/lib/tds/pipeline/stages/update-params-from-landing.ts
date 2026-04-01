/**
 * UpdateParamsFromLandingStage
 * 
 * Updates click parameters from landing page context.
 * Used in second level pipeline for LP → Offer flow.
 * 
 * Based on PHP: UpdateParamsFromLandingStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';


export class UpdateParamsFromLandingStage implements StageInterface {
  name = 'UpdateParamsFromLandingStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    const rawClick = payload.getRawClick();
    const landing = payload.getLanding();

    if (!rawClick) {
      return {
        success: false,
        payload,
        error: 'RawClick not set',
        abort: true
      };
    }

    // Update landing-related parameters from landing context
    if (landing) {
      // Mark that visitor has clicked through landing
      rawClick.landingClicked = true;
      rawClick.landingClickedAt = new Date();
      rawClick.landingUrl = landing.url;
      rawClick.landingId = landing.id;
      
      payload.log(`Landing clicked: ${landing.name}`);
    }

    // Update landing ID in raw click
    if (landing && rawClick.landingId !== landing.id) {
      rawClick.landingId = landing.id;
    }

    return { success: true, payload };
  }
}
