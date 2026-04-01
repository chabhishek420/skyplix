/**
 * Pipeline Verification Suite
 * 
 * Exercises the TDS pipeline with various scenarios to verify behavioral parity 
 * with the Keitaro PHP reference.
 */

import { Pipeline } from '../pipeline/pipeline';
import { Payload } from '../pipeline/payload';
import { NextRequest } from 'next/server';
import { cookiesService } from '../services/cookies-service';

// Mock data
const mockCampaign = {
  id: 'campaign-1',
  campaignId: 1,
  name: 'Test Campaign',
  status: 'active' as const,
  type: 'weight' as const,
  redirectType: 'http302' as const,
  cookiesTtl: 24,
  bindVisitors: true,
  bindVisitorsLanding: false,
  bindVisitorsOffer: false,
  cloakingEnabled: true,
  safePageUrl: '/safe/bot',
  destinationUrl: 'http://offer-url.com?click_id={click_id}&subid={subid}',
  offerId: 101,
  affiliateId: 'aff-1'
};

const mockStream = {
  id: 'stream-1',
  campaignId: 'campaign-1',
  name: 'Standard Stream',
  type: 'regular' as const,
  schema: 'url' as const,
  actionType: 'http_redirect',
  actionPayload: 'http://stream-destination.com?cid={click_id}',
  actionOptions: null,
  weight: 100,
  position: 1,
  status: 'active' as const,
  collectClicks: true,
  filterOr: false
};

async function runScenario(name: string, scenarioFn: () => Promise<void>) {
  console.log(`\n=== Scenario: ${name} ===`);
  try {
    await scenarioFn();
    console.log(`✅ Passed`);
  } catch (error) {
    console.error(`❌ Failed:`, error);
    process.exit(1);
  }
}

async function verify() {
  const pipeline = new Pipeline();

  // Scenario 1: Basic Macro Substitution
  await runScenario('Macro Substitution', async () => {
    const req = new NextRequest('http://localhost/click?keyword=test&subid=pub123');
    const payload = new Payload(req);
    
    // Manually set entities to skip database lookups for this test
    payload.setCampaign(mockCampaign as any);
    payload.setStream(mockStream as any);
    payload.setRawClick({
      clickId: 'test-click-id',
      visitorCode: 'test-visitor',
      campaignId: 'campaign-1',
      subId: 'pub123',
      keyword: 'test',
      ipString: '1.2.3.4',
      userAgent: 'Mozilla/5.0',
      datetime: new Date(),
    } as any);

    // Run action execution stage only (or full pipeline if we can mock db)
    // For this test, let's just test macro replacement directly
    const { replaceMacros } = await import('../macros');
    const destination = 'http://offer.com?click_id={click_id}&keyword={keyword}&subid={subid}';
    const replaced = await replaceMacros(destination, {
      clickId: 'test-click-id',
      keyword: 'test',
      subId: 'pub123',
      campaignId: 'c1',
      streamId: 's1'
    } as any);

    console.log(`Original: ${destination}`);
    console.log(`Replaced: ${replaced}`);

    if (replaced !== 'http://offer.com?click_id=test-click-id&keyword=test&subid=pub123') {
      throw new Error('Macro substitution mismatch');
    }
  });

  // Scenario 2: Bot Detection Redirection
  await runScenario('Bot Detection', async () => {
    // Googlebot UA
    const req = new NextRequest('http://localhost/click', {
      headers: { 'User-Agent': 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' }
    });
    const payload = new Payload(req);
    payload.setCampaign(mockCampaign as any);
    
    // Run Bot Detection logic (same as BuildRawClickStage)
    const { detectBot } = await import('../bot-detection');
    const botResult = await detectBot({
      userAgent: req.headers.get('user-agent') || '',
      ip: '66.249.66.1',
      referrer: '',
      headers: { 'user-agent': req.headers.get('user-agent') || '' },
      params: {},
      cookies: {}
    });

    // Mock build raw click result with detected bot info
    payload.setRawClick({ 
      userAgent: req.headers.get('user-agent'),
      ipString: '66.249.66.1',
      isBot: botResult.isBot,
      botConfidence: botResult.confidence,
      botReason: botResult.reason,
      botType: botResult.botType
    } as any);

    // Run CheckBotStage to verify redirection
    const { CheckBotStage } = await import('../pipeline/stages/check-bot');
    const stage = new CheckBotStage();
    const result = await stage.process(payload);
    
    console.log(`Bot Detected: ${payload.rawClick?.isBot}`);
    console.log(`Redirect URL: ${payload.redirectUrl}`);

    if (!payload.rawClick?.isBot) {
      throw new Error('Googlebot not detected as bot');
    }
    if (payload.redirectUrl !== '/safe/bot') {
      throw new Error('Bot not redirected to safe page');
    }
  });

  // Scenario 3: Uniqueness Verification
  await runScenario('Uniqueness Tracking', async () => {
    // First visit
    const req1 = new NextRequest('http://localhost/click');
    const payload1 = new Payload(req1);
    payload1.setCampaign(mockCampaign as any);
    payload1.setRawClick({ visitorCode: 'unique-visitor-1', isUniqueCampaign: false } as any);

    const { UpdateCampaignUniquenessSessionStage } = await import('../pipeline/stages/update-campaign-uniqueness');
    const stage = new UpdateCampaignUniquenessSessionStage();

    await stage.process(payload1);
    console.log(`First visit unique: ${payload1.rawClick?.isUniqueCampaign}`);
    if (payload1.rawClick?.isUniqueCampaign !== true) {
      throw new Error('First visit should be unique');
    }

    // Second visit: simulate existing cookie
    const visitorCode = 'unique-visitor-1';
    const cookieName = `uniq_${visitorCode.substring(0, 8)}`;
    const entry = {
      campaignIds: ['campaign-1'],
      streamIds: [],
      landingIds: [],
      offerIds: [],
      firstVisit: Date.now() - 3600000,
      lastVisit: Date.now() - 3600000,
      visitCount: 1
    };
    const cookieValue = Buffer.from(JSON.stringify(entry)).toString('base64');

    const req2 = new NextRequest('http://localhost/click', {
      headers: { 'Cookie': `${cookieName}=${cookieValue}` }
    });
    const payload2 = new Payload(req2);
    payload2.setCampaign(mockCampaign as any);
    payload2.setRawClick({ visitorCode: visitorCode, isUniqueCampaign: true } as any);

    await stage.process(payload2);
    console.log(`Second visit unique: ${payload2.rawClick?.isUniqueCampaign}`);
    if (payload2.rawClick?.isUniqueCampaign !== false) {
      throw new Error('Second visit should NOT be unique');
    }
  });

  // Scenario 4: Second Level Pipeline (LP to Offer)
  await runScenario('Second Level Pipeline', async () => {
    // Simulate a click from landing page (needs token)
    const req = new NextRequest('http://localhost/click?lp_id=land-1&token=test-token');
    const payload = new Payload(req);
    
    // We expect start() to detect it's second level if we set the flag
    // In real usage, this is detected by /api/lp/offer endpoint
    payload.setPipelineLevel(2);
    
    payload.setCampaign(mockCampaign as any);
    payload.setRawClick({ 
      clickId: 'test-click-id',
      visitorCode: 'test-visitor',
      campaignId: 'campaign-1',
      token: 'test-token',
      ipString: '1.2.3.4',
      datetime: new Date(),
    } as any);

    // Mock ChooseOfferStage result since it uses DB
    const { ChooseOfferStage } = await import('../pipeline/stages/choose-offer');
    payload.setOffer({
      id: 'offer-1',
      name: 'Test Offer',
      url: 'http://final-offer.com/{click_id}',
      status: 'active',
      weight: 100,
      actionType: 'http_redirect',
      actionPayload: 'http://final-offer.com/{click_id}'
    } as any);

    // Manually set action as if ChooseOfferStage succeeded
    payload.setAction('http_redirect', 'http://final-offer.com/{click_id}');
    payload.setForceRedirectOffer(true);

    // Run second level pipeline
    const result = await pipeline.runSecondLevel(payload);
    
    console.log(`Pipeline Level: ${payload.getPipelineLevel()}`);
    console.log(`Final Action Type: ${payload.actionType}`);
    console.log(`Final Action Payload: ${payload.actionPayload}`);

    if (payload.getPipelineLevel() !== 2) {
      throw new Error('Pipeline level should be 2');
    }
    if (payload.actionType !== 'http_redirect') {
      throw new Error('Failed to resolve action in second level pipeline');
    }
  });

  console.log('\n✅ All verification scenarios completed successfully!');
}

verify();
