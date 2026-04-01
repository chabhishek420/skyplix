/**
 * Pipeline
 * Executes traffic processing stages sequentially
 * 
 * Based on Keitaro PHP Pipeline architecture:
 * - First level stages: Full click processing (23 stages)
 * - Second level stages: LP→Offer flow (13 stages)
 * - Recursion limit: MAX_REPEATS = 10
 */

import type { StageInterface, StageResult } from './types';
import { Payload } from './payload';

// Import all first level stages
import { DomainRedirectStage } from './stages/domain-redirect';
import { CheckPrefetchStage } from './stages/check-prefetch';
import { BuildRawClickStage } from './stages/build-raw-click';
import { CheckBotStage } from './stages/check-bot';
import { FindCampaignStage } from './stages/find-campaign';
import { CheckDefaultCampaignStage } from './stages/check-default-campaign';
import { UpdateRawClickStage } from './stages/update-raw-click';
import { CheckParamAliasesStage } from './stages/check-param-aliases';
import { UpdateCampaignUniquenessSessionStage } from './stages/update-campaign-uniqueness';
import { ChooseStreamStage } from './stages/choose-stream';
import { UpdateStreamUniquenessSessionStage } from './stages/update-stream-uniqueness';
import { ChooseLandingStage } from './stages/choose-landing';
import { ChooseOfferStage } from './stages/choose-offer';
import { GenerateTokenStage } from './stages/generate-token';
import { FindAffiliateNetworkStage } from './stages/find-affiliate-network';
import { UpdateHitLimitStage } from './stages/update-hit-limit';
import { UpdateCostsStage } from './stages/update-costs';
import { UpdatePayoutStage } from './stages/update-payout';
import { SaveUniquenessSessionStage } from './stages/save-uniqueness-session';
import { SetCookieStage } from './stages/set-cookie';
import { ExecuteActionStage } from './stages/execute-action';
import { PrepareRawClickToStoreStage } from './stages/prepare-raw-click-to-store';
import { CheckSendingToAnotherCampaignStage } from './stages/check-sending-to-another-campaign';
import { StoreRawClicksStage } from './stages/store-raw-clicks';

// Import second level stages (for LP→Offer flow)
import { UpdateParamsFromLandingStage } from './stages/update-params-from-landing';

export class Pipeline {
  private firstLevelStages: StageInterface[] = [];
  private secondLevelStages: StageInterface[] = [];
  private stagesFrozen = false;

  constructor() {
    this.initializeFirstLevelStages();
    this.initializeSecondLevelStages();
  }

  /**
   * Initialize first level stages (full click processing)
   * Matches PHP: Pipeline::firstLevelStages()
   */
  private initializeFirstLevelStages(): void {
    this.firstLevelStages = [
      new DomainRedirectStage(),
      new CheckPrefetchStage(),
      new BuildRawClickStage(),
      new CheckBotStage(),
      new FindCampaignStage(),
      new CheckDefaultCampaignStage(),
      new UpdateRawClickStage(),
      new CheckParamAliasesStage(),
      new UpdateCampaignUniquenessSessionStage(),
      new ChooseStreamStage(),
      new UpdateStreamUniquenessSessionStage(),
      new ChooseLandingStage(),
      new ChooseOfferStage(),
      new GenerateTokenStage(),
      new FindAffiliateNetworkStage(),
      new UpdateHitLimitStage(),
      new UpdateCostsStage(),
      new UpdatePayoutStage(),
      new SaveUniquenessSessionStage(),
      new SetCookieStage(),
      new ExecuteActionStage(),
      new PrepareRawClickToStoreStage(),
      new CheckSendingToAnotherCampaignStage(),
      new StoreRawClicksStage()
    ];
  }

  /**
   * Initialize second level stages (LP→Offer flow)
   * Matches PHP: Pipeline::secondLevelStages()
   * 
   * Note: This is for when visitor clicks through landing page to offer.
   * Different from first level: no BuildRawClick, different stage order.
   */
  private initializeSecondLevelStages(): void {
    this.secondLevelStages = [
      new FindCampaignStage(),
      new UpdateParamsFromLandingStage(),
      new CheckDefaultCampaignStage(),
      new CheckParamAliasesStage(),
      new ChooseStreamStage(),
      new ChooseOfferStage(),
      new FindAffiliateNetworkStage(),
      new UpdateCostsStage(),
      new UpdatePayoutStage(),
      new SetCookieStage(),
      new ExecuteActionStage(),
      new CheckSendingToAnotherCampaignStage(),
      new StoreRawClicksStage()
    ];
  }

  /**
   * Freeze stages (prevent re-initialization during recursion)
   */
  freezeStages(): this {
    this.stagesFrozen = true;
    return this;
  }

  /**
   * Run first level pipeline
   */
  async runFirstLevel(payload: Payload): Promise<StageResult> {
    payload.setPipelineLevel(1);
    payload.log('Starting first level pipeline');
    
    return this.runStages(payload, this.firstLevelStages);
  }

  /**
   * Run second level pipeline (LP→Offer flow)
   */
  async runSecondLevel(payload: Payload): Promise<StageResult> {
    payload.setPipelineLevel(2);
    payload.log('Starting second level pipeline (LP→Offer)');
    
    return this.runStages(payload, this.secondLevelStages);
  }

  /**
   * Run stages with recursion handling
   * Matches PHP: Pipeline::_run()
   */
  private async runStages(payload: Payload, stages: StageInterface[]): Promise<StageResult> {
    for (const stage of stages) {
      try {
        const result = await stage.process(payload);
        payload.log(`Stage [${stage.name}] completed`);

        // Check if aborted
        if (payload.aborted || result.abort) {
          payload.log(`Pipeline aborted at stage [${stage.name}]`);
          
          // Check for forced campaign redirect (recursion handling)
          if (payload.forcedCampaignId && !payload.isMaxRepeatsExceeded()) {
            payload.log(`Redirecting to forced campaign: ${payload.forcedCampaignId}`);
            payload.resetForCampaignRedirect();
            payload.forcedCampaignId = payload.forcedCampaignId; // Re-set after reset
            
            // Recursively run first level pipeline again
            if (!this.stagesFrozen) {
              this.freezeStages();
            }
            return this.runStages(payload, this.firstLevelStages);
          } else if (payload.isMaxRepeatsExceeded()) {
            payload.log('CRITICAL: Max recursion limit exceeded, aborting to prevent infinite loop');
            return {
              success: false,
              payload,
              error: 'Max recursion limit exceeded - possible infinite loop detected',
              abort: true
            };
          }
          
          return result;
        }

        if (!result.success) {
          return result;
        }
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        payload.log(`Stage [${stage.name}] error: ${errorMessage}`);
        return {
          success: false,
          payload,
          error: errorMessage,
          abort: true
        };
      }
    }

    return { success: true, payload };
  }

  /**
   * Start pipeline with automatic level detection
   */
  async start(payload: Payload): Promise<StageResult> {
    // Auto-detect if second level (has landing clicked flag)
    if (payload.isSecondLevel()) {
      return this.runSecondLevel(payload);
    }
    return this.runFirstLevel(payload);
  }

  /**
   * Create default pipeline instance
   */
  static createDefault(): Pipeline {
    return new Pipeline();
  }
}
