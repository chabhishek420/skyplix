import { NextRequest, NextResponse } from 'next/server';
import { checkAuth } from '@/lib/auth';

// Basic standard templates for traffic sources
const TRAFFIC_SOURCE_TEMPLATES = [
  {
    id: 'propellerads',
    name: 'PropellerAds',
    type: 'pop',
    keywordParam: 'zoneid',
    costParam: 'cost',
    sourceParam: 'campaignid',
    useReferrer: true,
    postbackType: 'get',
    postbackParams: { clickid: '{visitor_code}', payout: '{payout}' }
  },
  {
    id: 'exoclick',
    name: 'ExoClick',
    type: 'adult',
    keywordParam: 'keyword',
    costParam: 'cost',
    sourceParam: 'campaign_id',
    useReferrer: true,
    postbackType: 'get',
    postbackParams: { id: '{sub1}', value: '{payout}' }
  },
  {
    id: 'mgid',
    name: 'MGID',
    type: 'native',
    keywordParam: 'c',
    costParam: 'click_price',
    sourceParam: 'c',
    useReferrer: true,
    postbackType: 'get',
    postbackParams: { c: '{sub1}', e: '{payout}' }
  },
  {
    id: 'facebook',
    name: 'Facebook',
    type: 'social',
    keywordParam: 'ad_id',
    costParam: 'cost',
    sourceParam: 'campaign_id',
    useReferrer: true,
    postbackType: 'post',
    postbackParams: { fbclid: '{extraParam1}' }
  },
  {
    id: 'googleads',
    name: 'Google Ads',
    type: 'search',
    keywordParam: 'keyword',
    costParam: 'cost',
    sourceParam: 'campaignid',
    useReferrer: true,
    postbackType: 'get',
    postbackParams: { gclid: '{extraParam1}' }
  }
];

// GET /api/admin/templates/traffic-sources - List standard traffic templates
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  return NextResponse.json({
    success: true,
    data: TRAFFIC_SOURCE_TEMPLATES
  });
}
