/**
 * Reports Admin API
 * Date-range analytics and reporting
 * 
 * Following Keitaro AdminApiContext pattern:
 * - All admin endpoints require authentication
 */

import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { Prisma } from '@prisma/client';
import { checkAuth } from '@/lib/auth';

// GET /api/admin/reports - Get report data
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const { searchParams } = new URL(request.url);
    const reportType = searchParams.get('type') || 'overview';
    const startDate = searchParams.get('startDate');
    const endDate = searchParams.get('endDate');
    const campaignId = searchParams.get('campaignId');
    const publisherId = searchParams.get('publisherId');
    const groupBy = searchParams.get('groupBy') || 'day'; // day, campaign, publisher, country

    // Parse dates
    const start = startDate ? new Date(startDate) : new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    const end = endDate ? new Date(endDate) : new Date();

    // Adjust end date to include the full day
    end.setHours(23, 59, 59, 999);

    let data: unknown = {};

    switch (reportType) {
      case 'overview':
        data = await getOverviewReport(start, end, campaignId, publisherId);
        break;
      
      case 'clicks':
        data = await getClicksReport(start, end, campaignId, publisherId, groupBy);
        break;
      
      case 'conversions':
        data = await getConversionsReport(start, end, campaignId, publisherId);
        break;
      
      case 'campaigns':
        data = await getCampaignsReport(start, end);
        break;
      
      case 'publishers':
        data = await getPublishersReport(start, end);
        break;
      
      case 'geo':
        data = await getGeoReport(start, end, campaignId);
        break;
      
      case 'devices':
        data = await getDevicesReport(start, end, campaignId);
        break;
      
      default:
        data = await getOverviewReport(start, end, campaignId, publisherId);
    }

    return NextResponse.json({
      success: true,
      data,
      meta: {
        reportType,
        startDate: start,
        endDate: end,
        groupBy
      }
    });
  } catch (error) {
    console.error('Error generating report:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to generate report' },
      { status: 500 }
    );
  }
}

/**
 * Overview report - summary statistics
 */
async function getOverviewReport(
  start: Date, 
  end: Date, 
  campaignId?: string | null,
  publisherId?: string | null
) {
  const clickWhere: Record<string, unknown> = {
    clickedAt: { gte: start, lte: end }
  };
  if (campaignId) clickWhere.campaignId = campaignId;
  if (publisherId) clickWhere.publisherId = publisherId;

  const [
    totalClicks,
    uniqueClicks,
    bots,
    totalConversions,
    totalRevenue,
    totalCost
  ] = await Promise.all([
    // Total clicks
    db.click.count({ where: clickWhere }),
    
    // Unique clicks
    db.click.count({
      where: { ...clickWhere, isUniqueCampaign: true }
    }),
    
    // Bot detections
    db.click.count({
      where: { ...clickWhere, isBot: true }
    }),
    
    // Total conversions
    db.conversion.count({
      where: {
        postbackAt: { gte: start, lte: end },
        campaignId: campaignId || undefined
      }
    }),
    
    // Total revenue
    db.conversion.aggregate({
      where: {
        postbackAt: { gte: start, lte: end },
        campaignId: campaignId || undefined
      },
      _sum: { payout: true }
    }),
    
    // Total cost
    db.click.aggregate({
      where: clickWhere,
      _sum: { cost: true }
    })
  ]);

  return {
    totalClicks,
    uniqueClicks,
    bots,
    conversionRate: totalClicks > 0 ? ((totalConversions / totalClicks) * 100).toFixed(2) : '0',
    totalConversions,
    totalRevenue: totalRevenue._sum.payout || 0,
    totalCost: totalCost._sum.cost || 0,
    profit: (totalRevenue._sum.payout || 0) - (totalCost._sum.cost || 0)
  };
}

/**
 * Clicks report - detailed click statistics
 */
async function getClicksReport(
  start: Date,
  end: Date,
  campaignId?: string | null,
  publisherId?: string | null,
  groupBy?: string
) {
  const where: Record<string, unknown> = {
    clickedAt: { gte: start, lte: end }
  };
  if (campaignId) where.campaignId = campaignId;
  if (publisherId) where.publisherId = publisherId;

  let groupedData: unknown[] = [];

  switch (groupBy) {
    case 'campaign': {
      const grouped = await db.click.groupBy({
        by: ['campaignId'],
        where,
        _count: true,
        _sum: { cost: true }
      });
      
      // Get campaign names
      const campaignIds = grouped.map(g => g.campaignId).filter(Boolean) as string[];
      const campaigns = await db.campaign.findMany({
        where: { id: { in: campaignIds } },
        select: { id: true, name: true }
      });
      const campaignMap = new Map(campaigns.map(c => [c.id, c.name]));
      
      groupedData = grouped.map(g => ({
        campaignId: g.campaignId,
        campaignName: campaignMap.get(g.campaignId as string) || 'Unknown',
        clicks: g._count,
        cost: g._sum.cost || 0
      }));
      break;
    }

    case 'publisher': {
      groupedData = await db.click.groupBy({
        by: ['publisherId'],
        where,
        _count: true
      });
      break;
    }

    case 'country': {
      const grouped = await db.click.groupBy({
        by: ['country'],
        where,
        _count: true,
        _sum: { cost: true }
      });
      groupedData = grouped.map(g => ({
        country: g.country || 'Unknown',
        clicks: g._count,
        cost: g._sum.cost || 0
      }));
      break;
    }

    case 'day':
    default:
      // Group by day using raw query for SQLite
      const clicksByDay = await db.$queryRaw`
        SELECT 
          DATE(clickedAt) as date,
          COUNT(*) as clicks,
          SUM(CASE WHEN isBot = 1 THEN 1 ELSE 0 END) as bots,
          SUM(cost) as cost
        FROM Click
        WHERE clickedAt >= ${start} AND clickedAt <= ${end}
        ${campaignId ? Prisma.sql`AND campaignId = ${campaignId}` : Prisma.empty}
        GROUP BY DATE(clickedAt)
        ORDER BY date DESC
      `;
      groupedData = clicksByDay as unknown[];
  }

  return {
    grouped: groupedData,
    total: await db.click.count({ where })
  };
}

/**
 * Conversions report - conversion statistics
 */
async function getConversionsReport(
  start: Date,
  end: Date,
  campaignId?: string | null,
  _publisherId?: string | null
) {
  const where: Record<string, unknown> = {
    postbackAt: { gte: start, lte: end }
  };
  if (campaignId) where.campaignId = campaignId;

  const conversions = await db.conversion.findMany({
    where,
    include: {
      click: {
        select: {
          clickId: true,
          country: true,
          deviceType: true,
          publisherId: true
        }
      }
    },
    orderBy: { postbackAt: 'desc' },
    take: 100
  });

  const summary = await db.conversion.aggregate({
    where,
    _count: true,
    _sum: { payout: true, revenue: true }
  });

  return {
    conversions,
    summary: {
      total: summary._count,
      totalPayout: summary._sum.payout || 0,
      totalRevenue: summary._sum.revenue || 0
    }
  };
}

/**
 * Campaigns report - performance by campaign
 */
async function getCampaignsReport(start: Date, end: Date) {
  const campaigns = await db.campaign.findMany({
    where: { status: 'active' },
    select: {
      id: true,
      campaignId: true,
      name: true,
      status: true,
      _count: {
        select: {
          clicks: {
            where: { clickedAt: { gte: start, lte: end } }
          },
          conversions: {
            where: { postbackAt: { gte: start, lte: end } }
          }
        }
      }
    }
  });

  // Get revenue per campaign
  const revenueByCampaign = await db.conversion.groupBy({
    by: ['campaignId'],
    where: { postbackAt: { gte: start, lte: end } },
    _sum: { payout: true }
  });

  const revenueMap = new Map(
    revenueByCampaign.map(r => [r.campaignId, r._sum.payout || 0])
  );

  return campaigns.map(c => ({
    id: c.id,
    campaignId: c.campaignId,
    name: c.name,
    status: c.status,
    clicks: c._count.clicks,
    conversions: c._count.conversions,
    revenue: revenueMap.get(c.id) || 0,
    conversionRate: c._count.clicks > 0 
      ? ((c._count.conversions / c._count.clicks) * 100).toFixed(2)
      : '0'
  }));
}

/**
 * Publishers report - performance by publisher
 */
async function getPublishersReport(start: Date, end: Date) {
  const publishers = await db.publisher.findMany({
    where: { status: 'active' },
    select: {
      id: true,
      pubId: true,
      name: true,
      totalClicks: true,
      totalConversions: true,
      totalRevenue: true
    }
  });

  // Get clicks in date range
  const clicksByPublisher = await db.click.groupBy({
    by: ['publisherId'],
    where: { clickedAt: { gte: start, lte: end } },
    _count: true
  });

  const clicksMap = new Map(
    clicksByPublisher.map(c => [c.publisherId, c._count])
  );

  return publishers.map(p => ({
    id: p.id,
    pubId: p.pubId,
    name: p.name,
    clicksInRange: clicksMap.get(p.id) || 0,
    totalClicks: p.totalClicks,
    totalConversions: p.totalConversions,
    totalRevenue: p.totalRevenue
  }));
}

/**
 * Geo report - geographic distribution
 */
async function getGeoReport(start: Date, end: Date, campaignId?: string | null) {
  const where: Record<string, unknown> = {
    clickedAt: { gte: start, lte: end }
  };
  if (campaignId) where.campaignId = campaignId;

  const byCountry = await db.click.groupBy({
    by: ['country'],
    where,
    _count: true,
    _sum: { cost: true }
  });

  const byCity = await db.click.groupBy({
    by: ['city', 'country'],
    where,
    _count: true,
    orderBy: { _count: { country: 'desc' } }
  });

  return {
    byCountry: byCountry.map(c => ({
      country: c.country || 'Unknown',
      clicks: c._count,
      cost: c._sum.cost || 0
    })),
    byCity: byCity.slice(0, 50).map(c => ({
      city: c.city || 'Unknown',
      country: c.country || 'Unknown',
      clicks: c._count
    }))
  };
}

/**
 * Devices report - device/browser/OS distribution
 */
async function getDevicesReport(start: Date, end: Date, campaignId?: string | null) {
  const where: Record<string, unknown> = {
    clickedAt: { gte: start, lte: end }
  };
  if (campaignId) where.campaignId = campaignId;

  const [byDeviceType, byBrowser, byOs] = await Promise.all([
    db.click.groupBy({
      by: ['deviceType'],
      where,
      _count: true
    }),
    db.click.groupBy({
      by: ['browser'],
      where,
      _count: true
    }),
    db.click.groupBy({
      by: ['os'],
      where,
      _count: true
    })
  ]);

  return {
    byDeviceType: byDeviceType.map(d => ({
      deviceType: d.deviceType || 'Unknown',
      clicks: d._count
    })),
    byBrowser: byBrowser.map(b => ({
      browser: b.browser || 'Unknown',
      clicks: b._count
    })),
    byOs: byOs.map(o => ({
      os: o.os || 'Unknown',
      clicks: o._count
    }))
  };
}
