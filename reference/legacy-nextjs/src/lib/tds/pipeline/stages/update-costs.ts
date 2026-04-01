/**
 * UpdateCostsStage
 * 
 * Updates click cost from traffic source parameters.
 * Based on Keitaro's UpdateCostsStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';


export class UpdateCostsStage implements StageInterface {
  name = 'UpdateCostsStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.rawClick) {
      return {
        success: true,
        payload
      };
    }

    const rawClick = payload.rawClick;
    const request = payload.request;

    if (!request) {
      return {
        success: true,
        payload
      };
    }

    const params = payload.getAllParams();
    const campaign = payload.campaign;

    // Get cost from parameters
    let cost: string | null = null;
    
    // 1. Try traffic source specific parameter
    if (campaign?.trafficSource?.costParam) {
      const tsParam = campaign.trafficSource.costParam;
      cost = params[tsParam] || null;
      if (cost) payload.log(`Found cost via traffic source param "${tsParam}": ${cost}`);
    }
    
    // 2. Fall back to standard parameters
    if (!cost) {
      cost = params['cost'] || params['cpc'] || params['c'] || null;
    }

    if (cost) {
      const costValue = parseFloat(cost);
      if (!isNaN(costValue)) {
        rawClick.cost = costValue;
        payload.log(`Applied click cost: ${costValue}`);
      }
    }


    // Get currency
    const currency = params['currency'] || params['cur'];
    if (currency) {
      (rawClick as any).currency = currency;
    }

    return {
      success: true,
      payload
    };
  }
}
