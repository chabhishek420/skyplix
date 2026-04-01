/**
 * Statistics API
 * 
 * Dashboard statistics and analytics
 * Requires admin authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/stats - Get dashboard statistics
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const period = searchParams.get('period') || 'today'; // today, 7days, 30days, all
    
    // Calculate date range
    const now = new Date();
    let startDate: Date;
    
    switch (period) {
      case 'today':
        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        break;
      case '7days':
        startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
        break;
      case '30days':
        startDate = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
        break;
      default:
        startDate = new Date(0);
    }
    
    // Get click stats
    const clicks = await db.click.count({
      where: { clickedAt: { gte: startDate } }
    });
    
    const uniqueClicks = await db.click.groupBy({
      by: ['ip'],
      where: { 
        clickedAt: { gte: startDate },
        isBot: false
      }
    });
    
    const bots = await db.click.count({
      where: { 
        clickedAt: { gte: startDate },
        isBot: true
      }
    });
    
    // Get conversion stats
    const conversions = await db.conversion.count({
      where: { 
        postbackAt: { gte: startDate },
        status: 'approved'
      }
    });
    
    const revenue = await db.conversion.aggregate({
      where: { 
        postbackAt: { gte: startDate },
        status: 'approved'
      },
      _sum: { payout: true }
    });
    
    // Get pending conversions
    const pendingConversions = await db.conversion.count({
      where: { status: 'pending' }
    });
    
    // Get active campaigns
    const activeCampaigns = await db.campaign.count({
      where: { status: 'active' }
    });
    
    // Get active publishers
    const activePublishers = await db.publisher.count({
      where: { status: 'active' }
    });
    
    // Get clicks by day (last 7 days)
    const clicksByDay = await db.$queryRaw<Array<{ date: string; count: number }>>`
      SELECT date(clickedAt) as date, COUNT(*) as count
      FROM Click
      WHERE clickedAt >= datetime('now', '-7 days')
      GROUP BY date(clickedAt)
      ORDER BY date DESC
    `;
    
    // Get top campaigns
    const topCampaigns = await db.click.groupBy({
      by: ['campaignId'],
      where: { 
        clickedAt: { gte: startDate },
        isBot: false
      },
      _count: true,
      orderBy: { _count: { campaignId: 'desc' } },
      take: 5
    });
    
    // Get campaign names for top campaigns
    const campaignIds = topCampaigns.map(c => c.campaignId).filter(Boolean) as string[];
    const campaigns = await db.campaign.findMany({
      where: { id: { in: campaignIds } },
      select: { id: true, name: true, campaignId: true }
    });
    
    const campaignMap = Object.fromEntries(
      campaigns.map(c => [c.id, c])
    );
    
    const topCampaignsWithNames = topCampaigns.map(c => ({
      ...c,
      campaign: c.campaignId ? campaignMap[c.campaignId] : null
    }));
    
    // Get top publishers
    const topPublishers = await db.click.groupBy({
      by: ['publisherId'],
      where: { 
        clickedAt: { gte: startDate },
        isBot: false
      },
      _count: true,
      orderBy: { _count: { publisherId: 'desc' } },
      take: 5
    });
    
    // Get publisher names
    const publisherIds = topPublishers.map(p => p.publisherId).filter(Boolean) as string[];
    const publishers = await db.publisher.findMany({
      where: { id: { in: publisherIds } },
      select: { id: true, name: true, pubId: true }
    });
    
    const publisherMap = Object.fromEntries(
      publishers.map(p => [p.id, p])
    );
    
    const topPublishersWithNames = topPublishers.map(p => ({
      ...p,
      publisher: p.publisherId ? publisherMap[p.publisherId] : null
    }));
    
    return NextResponse.json({
      period,
      stats: {
        clicks,
        uniqueClicks: uniqueClicks.length,
        bots,
        conversions,
        revenue: revenue._sum.payout || 0,
        pendingConversions,
        activeCampaigns,
        activePublishers,
        conversionRate: clicks > 0 ? ((conversions / clicks) * 100).toFixed(2) : '0'
      },
      charts: {
        clicksByDay,
        topCampaigns: topCampaignsWithNames,
        topPublishers: topPublishersWithNames
      }
    });
    
  } catch (error) {
    console.error('Error fetching stats:', error);
    return NextResponse.json({ error: 'Failed to fetch stats' }, { status: 500 });
  }
}
