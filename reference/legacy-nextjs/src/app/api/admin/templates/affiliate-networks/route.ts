import { NextRequest, NextResponse } from 'next/server';
import { checkAuth } from '@/lib/auth';

// Basic standard templates for affiliate networks (matching Keitaro standards)
const AFFILIATE_NETWORK_TEMPLATES = [
  {
    id: 'hasoffers',
    name: 'HasOffers',
    clickParam: 'aff_sub',
    payoutParam: 'payout',
    statusParam: 'status',
    defaultStatus: 'active'
  },
  {
    id: 'impact',
    name: 'Impact',
    clickParam: 'subId1',
    payoutParam: 'ActionTrackerAmount',
    statusParam: 'status',
    defaultStatus: 'active'
  },
  {
    id: 'affise',
    name: 'Affise',
    clickParam: 'clickid',
    payoutParam: 'sum',
    statusParam: 'status',
    defaultStatus: 'active'
  },
  {
    id: 'cake',
    name: 'CAKE',
    clickParam: 's1',
    payoutParam: 'price',
    statusParam: 'status',
    defaultStatus: 'active'
  },
  {
    id: 'everflow',
    name: 'Everflow',
    clickParam: 'sub1',
    payoutParam: 'payout',
    statusParam: 'status',
    defaultStatus: 'active'
  }
];

// GET /api/admin/templates/affiliate-networks - List standard network templates
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  return NextResponse.json({
    success: true,
    data: AFFILIATE_NETWORK_TEMPLATES
  });
}
