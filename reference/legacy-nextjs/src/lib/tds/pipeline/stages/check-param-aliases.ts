/**
 * CheckParamAliasesStage
 * 
 * Handles parameter aliases for common tracking parameters.
 * Allows short-form parameters to be mapped to full names.
 * Based on Keitaro's CheckParamAliasesStage.php
 */

import type { StageInterface, StageResult, PipelinePayload } from '../types';


// Parameter aliases mapping
const PARAM_ALIASES: Record<string, string> = {
  // Click ID aliases
  'clid': 'clickid',
  'click_id': 'clickid',
  'clickid': 'clickid',
  
  // Campaign aliases
  'camp': 'campaign_id',
  'cid': 'campaign_id',
  
  // Publisher aliases
  'pid': 'pub_id',
  'affiliate_id': 'pub_id',
  'aff_id': 'pub_id',
  
  // Sub ID aliases
  's1': 'sub_id_1',
  's2': 'sub_id_2',
  's3': 'sub_id_3',
  's4': 'sub_id_4',
  's5': 'sub_id_5',
  
  // Keyword aliases
  'kw': 'keyword',
  'q': 'keyword',
  'query': 'keyword',
  
  // Source aliases
  'src': 'source',
  
  // Referrer aliases
  'ref': 'referrer',
  'referer': 'referrer',
  
  // Creative aliases
  'cr': 'creative_id',
  'ad_id': 'creative_id',
  
  // Cost aliases
  'c': 'cost',
  'cpc': 'cost',
  
  // External ID aliases
  'eid': 'external_id',
  'tx_id': 'external_id',
};

export class CheckParamAliasesStage implements StageInterface {
  name = 'CheckParamAliasesStage';

  async process(payload: PipelinePayload): Promise<StageResult> {
    if (!payload.request) {
      return {
        success: true,
        payload
      };
    }

    const url = new URL(payload.request.url);
    const params = new URLSearchParams(url.search);
    const aliasesApplied: string[] = [];

    // Process each parameter
    for (const [key, value] of params.entries()) {
      const normalizedKey = key.toLowerCase();
      
      // Check if this is an alias
      if (PARAM_ALIASES[normalizedKey]) {
        const canonicalName = PARAM_ALIASES[normalizedKey];
        
        // Don't override if canonical name already exists
        if (!params.has(canonicalName)) {
          params.set(canonicalName, value);
          aliasesApplied.push(`${normalizedKey} -> ${canonicalName}`);
        }
      }
    }

    if (aliasesApplied.length > 0) {
      payload.log(`Parameter aliases applied: ${aliasesApplied.join(', ')}`);
      
      // Update the URL in the request (note: we can't modify the request directly,
      // but we can store the normalized params for later use)
      payload.log(`Normalized URL: ${url.pathname}?${params.toString()}`);
    }

    return {
      success: true,
      payload
    };
  }
}
