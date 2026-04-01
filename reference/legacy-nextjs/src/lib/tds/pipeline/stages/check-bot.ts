/**
 * CheckBotStage
 *
 * Checks if the visitor is a bot and redirects to safe page if so.
 * This is the core Cloaking Layer stage.
 *
 * Based on Keitaro's bot detection flow:
 * - Check isBot flag set by BuildRawClickStage
 * - Check bot confidence threshold (default 70%)
 * - Redirect to safe page URL or default /safe
 *
 * Pipeline position: After BuildRawClickStage, before FindCampaignStage
 */

import type { StageInterface, StageResult, PipelinePayload, RawClick, PipelinePayload } from '../types';

export class CheckBotStage implements StageInterface {
  name = 'CheckBotStage';

  /**
   * Confidence threshold for bot detection (0-100)
   * Higher = more strict (fewer false positives)
   * Lower = more lenient (catches more bots but may block real users)
   */
  private readonly CONFIDENCE_THRESHOLD = 70;

  async process(payload: PipelinePayload): Promise<StageResult> {
    const { rawClick, campaign } = payload;

    // No rawClick means nothing to check
    if (!rawClick) {
      return { success: true, payload };
    }

    // Check if bot with sufficient confidence
    if (rawClick.isBot) {
      const confidence = rawClick.botConfidence ?? 100;

      if (confidence >= this.CONFIDENCE_THRESHOLD) {
        // Determine safe page URL
        const safeUrl = this.getSafePageUrl(campaign, rawClick);

        payload.log(`Bot detected: ${rawClick.botReason} (confidence: ${confidence}%), redirecting to safe page: ${safeUrl}`);

        // Set redirect details and abort pipeline
        payload.setRedirect(safeUrl, 302);
        payload.abort();


        return {
          success: true,
          payload,
          abort: true
        };
      } else {
        payload.log(`Bot detected but confidence too low: ${confidence}% < ${this.CONFIDENCE_THRESHOLD}%`);
      }
    }

    return { success: true, payload };
  }

  /**
   * Get safe page URL based on campaign settings and bot type
   */
  private getSafePageUrl(campaign: PipelinePayload['campaign'], rawClick: PipelinePayload['rawClick']): string {
    // Campaign-specific safe page URL takes priority
    if (campaign?.safePageUrl) {
      return campaign.safePageUrl;
    }

    // Bot type specific safe pages
    const botType = rawClick?.botType;
    if (botType) {
      const safePageMap: Record<string, string> = {
        'crawler': '/api/safe',
        'scanner': '/api/safe',
        'tool': '/api/safe',
        'suspicious': '/api/safe',
        'debug': '/api/safe'
      };

      if (safePageMap[botType]) {
        return safePageMap[botType];
      }
    }

    // Default safe page
    return '/api/safe';
  }
}
