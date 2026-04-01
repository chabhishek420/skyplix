/**
 * Campaign Management API
 * 
 * CRUD operations for campaigns
 * Requires admin authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const CampaignSchema = z.object({
  name: z.string().min(1, "Name is required"),
  description: z.string().optional(),
  status: z.enum(['active', 'paused', 'archived', 'deleted']).default('active'),
  campaignId: z.number().optional(),
  offerId: z.string().optional(),
  affiliateId: z.string().optional(),
  destinationUrl: z.string().url("Invalid destination URL").optional().or(z.literal("")),
  dailyCap: z.number().nonnegative().optional(),
  totalCap: z.number().nonnegative().optional(),
  safePageUrl: z.string().optional().or(z.literal("")),
  safePageType: z.enum(['redirect', 'local', 'direct']).default('redirect'),
  streams: z.array(z.any()).optional()
});

// GET /api/admin/campaigns - List all campaigns
export async function GET(request: NextRequest) {
  // Check authentication
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const status = searchParams.get('status');
    const search = searchParams.get('search');
    
    const where: Record<string, unknown> = {};
    
    if (status) {
      where.status = status;
    }
    
    if (search) {
      where.OR = [
        { name: { contains: search } },
        { campaignId: { equals: parseInt(search) } }
      ];
    }
    
    const campaigns = await db.campaign.findMany({
      where,
      include: {
        streams: true,
        _count: {
          select: { clicks: true, conversions: true }
        }
      },
      orderBy: { createdAt: 'desc' }
    });
    
    return NextResponse.json({ campaigns });
    
  } catch (error) {
    console.error('Error fetching campaigns:', error);
    return NextResponse.json({ error: 'Failed to fetch campaigns' }, { status: 500 });
  }
}

// POST /api/admin/campaigns - Create new campaign
export async function POST(request: NextRequest) {
  // Check authentication
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = CampaignSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ 
        error: 'Validation failed', 
        details: result.error.format() 
      }, { status: 400 });
    }
    
    const body = result.data;
    
    // Generate campaign ID if not provided
    const campaignId = body.campaignId || await generateCampaignId();
    
    const campaign = await db.campaign.create({
      data: {
        campaignId,
        name: body.name,
        description: body.description,
        status: body.status || 'active',
        offerId: body.offerId,
        affiliateId: body.affiliateId,
        destinationUrl: body.destinationUrl,
        dailyCap: body.dailyCap,
        totalCap: body.totalCap,
        safePageUrl: body.safePageUrl,
        safePageType: body.safePageType || 'redirect',
        streams: {
          create: (body as any).streams || []
        }
      },
      include: { streams: true }
    });
    
    return NextResponse.json({ campaign });
    
  } catch (error) {
    console.error('Error creating campaign:', error);
    return NextResponse.json({ error: 'Failed to create campaign' }, { status: 500 });
  }
}

// PUT /api/admin/campaigns - Update campaign
export async function PUT(request: NextRequest) {
  // Check authentication
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const { id, ...data } = json;
    
    if (!id) {
      return NextResponse.json({ error: 'Campaign ID required' }, { status: 400 });
    }

    const result = CampaignSchema.partial().safeParse(data);
    if (!result.success) {
      return NextResponse.json({ 
        error: 'Validation failed', 
        details: result.error.format() 
      }, { status: 400 });
    }
    
    const validatedData = result.data;
    
    const campaign = await db.campaign.update({
      where: { id },
      data: {
        name: validatedData.name,
        description: validatedData.description,
        status: validatedData.status,
        offerId: validatedData.offerId,
        affiliateId: validatedData.affiliateId,
        destinationUrl: validatedData.destinationUrl,
        dailyCap: validatedData.dailyCap,
        totalCap: validatedData.totalCap,
        safePageUrl: validatedData.safePageUrl,
        safePageType: validatedData.safePageType
      },
      include: { streams: true }
    });
    
    return NextResponse.json({ campaign });
    
  } catch (error) {
    console.error('Error updating campaign:', error);
    return NextResponse.json({ error: 'Failed to update campaign' }, { status: 500 });
  }
}

// DELETE /api/admin/campaigns - Delete campaign
export async function DELETE(request: NextRequest) {
  // Check authentication
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get('id');
    
    if (!id) {
      return NextResponse.json({ error: 'Campaign ID required' }, { status: 400 });
    }
    
    // Soft delete - just mark as deleted
    const campaign = await db.campaign.update({
      where: { id },
      data: { status: 'deleted' }
    });
    
    return NextResponse.json({ success: true, campaign });
    
  } catch (error) {
    console.error('Error deleting campaign:', error);
    return NextResponse.json({ error: 'Failed to delete campaign' }, { status: 500 });
  }
}

/**
 * Generate next campaign ID
 */
async function generateCampaignId(): Promise<number> {
  const lastCampaign = await db.campaign.findFirst({
    orderBy: { campaignId: 'desc' }
  });
  
  return (lastCampaign?.campaignId || 10000) + 1;
}
