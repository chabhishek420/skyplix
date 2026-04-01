/**
 * Database Seed Script
 * 
 * Populates the database with sample data for testing
 */

import { PrismaClient } from '@prisma/client';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Seeding database...');

  // Create sample campaigns
  const campaigns = await Promise.all([
    prisma.campaign.create({
      data: {
        campaignId: 10115,
        name: 'Hostinger Main Offer',
        description: 'Primary Hostinger hosting offer',
        status: 'active',
        offerId: 753,
        affiliateId: '1636',
        destinationUrl: 'https://www.hostg.xyz/aff_c',
        safePageUrl: '/safe',
        safePageType: 'redirect'
      }
    }),
    prisma.campaign.create({
      data: {
        campaignId: 10116,
        name: 'VPN Pro Offer',
        description: 'VPN service promotion',
        status: 'active',
        offerId: 892,
        affiliateId: '1636',
        destinationUrl: 'https://vpn.example.com/track',
        safePageUrl: '/safe',
        safePageType: 'redirect'
      }
    }),
    prisma.campaign.create({
      data: {
        campaignId: 10117,
        name: 'E-commerce Deal',
        description: 'Shopping platform affiliate',
        status: 'active',
        offerId: 1024,
        affiliateId: '151905',
        destinationUrl: 'https://shop.example.com/aff',
        safePageUrl: '/safe',
        safePageType: 'redirect'
      }
    }),
    prisma.campaign.create({
      data: {
        campaignId: 10118,
        name: 'Travel Booking',
        description: 'Travel affiliate program',
        status: 'paused',
        offerId: 2048,
        affiliateId: '1636',
        destinationUrl: 'https://travel.example.com/book',
        safePageUrl: '/safe',
        safePageType: 'redirect'
      }
    }),
    prisma.campaign.create({
      data: {
        campaignId: 10119,
        name: 'Finance App',
        description: 'Fintech referral program',
        status: 'active',
        offerId: 3072,
        affiliateId: '1REQUIREFOR51',
        destinationUrl: 'https://finance.example.com/ref',
        safePageUrl: '/safe',
        safePageType: 'redirect'
      }
    })
  ]);

  console.log(`✅ Created ${campaigns.length} campaigns`);

  // Create sample publishers
  const publishers = await Promise.all([
    prisma.publisher.create({
      data: {
        pubId: 102200,
        name: 'Google Ads Traffic',
        email: 'traffic@google-ads.com',
        status: 'active',
        source: 'google'
      }
    }),
    prisma.publisher.create({
      data: {
        pubId: 102201,
        name: 'Facebook Campaigns',
        email: 'social@facebook.com',
        status: 'active',
        source: 'facebook'
      }
    }),
    prisma.publisher.create({
      data: {
        pubId: 102202,
        name: 'Native Advertising',
        email: 'native@taboola.com',
        status: 'active',
        source: 'taboola'
      }
    }),
    prisma.publisher.create({
      data: {
        pubId: 102203,
        name: 'SEO Organic',
        email: 'seo@organic.com',
        status: 'active',
        source: 'seo'
      }
    }),
    prisma.publisher.create({
      data: {
        pubId: 102204,
        name: 'Email Marketing',
        email: 'email@marketing.com',
        status: 'active',
        source: 'email'
      }
    }),
    prisma.publisher.create({
      data: {
        pubId: 102205,
        name: 'Blocked Publisher',
        email: 'blocked@spam.com',
        status: 'blocked',
        source: 'spam'
      }
    })
  ]);

  console.log(`✅ Created ${publishers.length} publishers`);

  // Create campaign-publisher access
  const activePublishers = publishers.filter(p => p.status === 'active');
  const activeCampaigns = campaigns.filter(c => c.status === 'active');

  for (const campaign of activeCampaigns) {
    for (const publisher of activePublishers) {
      await prisma.campaignPublisher.create({
        data: {
          campaignId: campaign.id,
          publisherId: publisher.id,
          status: 'active'
        }
      });
    }
  }

  console.log(`✅ Created campaign-publisher access rules`);

  // Create sample bot rules
  const botRules = await Promise.all([
    prisma.botRule.create({
      data: {
        name: 'Block Debug Parameters',
        type: 'parameter',
        pattern: 'debug|test|dev|admin',
        matchType: 'regex',
        action: 'safe_page',
        priority: 100,
        status: 'active'
      }
    }),
    prisma.botRule.create({
      data: {
        name: 'Block Known Bots',
        type: 'user_agent',
        pattern: 'googlebot|bingbot|slurp|duckduckbot',
        matchType: 'regex',
        action: 'safe_page',
        priority: 90,
        status: 'active'
      }
    }),
    prisma.botRule.create({
      data: {
        name: 'Block SEO Tools',
        type: 'user_agent',
        pattern: 'ahrefsbot|semrushbot|mj12bot',
        matchType: 'regex',
        action: 'safe_page',
        priority: 85,
        status: 'active'
      }
    }),
    prisma.botRule.create({
      data: {
        name: 'Block Security Scanners',
        type: 'user_agent',
        pattern: 'nikto|sqlmap|nmap|masscan',
        matchType: 'regex',
        action: 'safe_page',
        priority: 95,
        status: 'active'
      }
    }),
    prisma.botRule.create({
      data: {
        name: 'Block Headless Browsers',
        type: 'user_agent',
        pattern: 'headless|phantomjs|selenium|puppeteer|playwright',
        matchType: 'regex',
        action: 'safe_page',
        priority: 80,
        status: 'active'
      }
    })
  ]);

  console.log(`✅ Created ${botRules.length} bot rules`);

  // Create sample safe pages
  const safePages = await Promise.all([
    prisma.safePage.create({
      data: {
        name: 'Default Safe Page',
        type: 'redirect',
        url: '/safe',
        language: 'en',
        status: 'active'
      }
    }),
    prisma.safePage.create({
      data: {
        name: 'Chinese Safe Page',
        type: 'redirect',
        url: '/zh-CN',
        language: 'zh-CN',
        status: 'active'
      }
    }),
    prisma.safePage.create({
      data: {
        name: 'Russian Safe Page',
        type: 'redirect',
        url: '/ru',
        language: 'ru',
        status: 'active'
      }
    }),
    prisma.safePage.create({
      data: {
        name: 'German Safe Page',
        type: 'redirect',
        url: '/de',
        language: 'de',
        status: 'active'
      }
    })
  ]);

  console.log(`✅ Created ${safePages.length} safe pages`);

  // Create sample affiliate network
  const network = await prisma.affiliateNetwork.create({
    data: {
      name: 'HasOffers / Tune',
      baseUrl: 'hostinger-elb.go2cloud.org',
      clickParam: 'clickid',
      payoutParam: 'payout',
      statusParam: 'status',
      status: 'active'
    }
  });

  console.log(`✅ Created affiliate network: ${network.name}`);

  // Create sample clicks and conversions for stats
  const now = new Date();
  const clicksData = [];

  // Generate clicks for the last 7 days
  for (let day = 0; day < 7; day++) {
    const dayStart = new Date(now);
    dayStart.setDate(dayStart.getDate() - day);
    dayStart.setHours(0, 0, 0, 0);

    const clicksPerDay = Math.floor(Math.random() * 100) + 50;

    for (let i = 0; i < clicksPerDay; i++) {
      const timestamp = new Date(dayStart);
      timestamp.setHours(Math.floor(Math.random() * 24));
      timestamp.setMinutes(Math.floor(Math.random() * 60));
      timestamp.setSeconds(Math.floor(Math.random() * 60));

      const campaignIdx = Math.floor(Math.random() * activeCampaigns.length);
      const publisherIdx = Math.floor(Math.random() * activePublishers.length);
      const isBot = Math.random() < 0.15; // 15% bot traffic

      const timestampHex = Math.floor(timestamp.getTime() / 1000).toString(16).padStart(8, '0');
      const randomHex = Math.random().toString(16).substring(2, 18);

      clicksData.push({
        clickId: timestampHex + randomHex,
        campaignId: activeCampaigns[campaignIdx].id,
        publisherId: activePublishers[publisherIdx].id,
        ip: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
        userAgent: isBot 
          ? 'Googlebot/2.1 (+http://www.google.com/bot.html)'
          : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        referrer: Math.random() > 0.5 ? 'https://google.com' : 'https://facebook.com',
        isBot,
        botReason: isBot ? 'Bot user agent detected: googlebot' : null,
        showedSafePage: isBot,
        clickedAt: timestamp,
        destinationUrl: isBot ? null : activeCampaigns[campaignIdx].destinationUrl
      });
    }
  }

  // Insert clicks in batches
  const batchSize = 100;
  for (let i = 0; i < clicksData.length; i += batchSize) {
    const batch = clicksData.slice(i, i + batchSize);
    await prisma.click.createMany({ data: batch });
  }

  console.log(`✅ Created ${clicksData.length} sample clicks`);

  // Create some conversions for non-bot clicks
  const validClicks = clicksData.filter(c => !c.isBot);
  const conversionRate = 0.05; // 5% conversion rate
  let conversionsCreated = 0;

  for (const click of validClicks) {
    if (Math.random() < conversionRate) {
      await prisma.conversion.create({
        data: {
          clickId: click.clickId,
          campaignId: click.campaignId,
          status: Math.random() > 0.1 ? 'approved' : 'pending',
          payout: Math.floor(Math.random() * 50) + 10,
          revenue: Math.floor(Math.random() * 50) + 10,
          postbackAt: new Date(click.clickedAt.getTime() + Math.random() * 3600000)
        }
      });
      conversionsCreated++;
    }
  }

  console.log(`✅ Created ${conversionsCreated} sample conversions`);

  console.log('\n🎉 Database seeding complete!');
  console.log('\n📊 Summary:');
  console.log(`   Campaigns: ${campaigns.length}`);
  console.log(`   Publishers: ${publishers.length}`);
  console.log(`   Bot Rules: ${botRules.length}`);
  console.log(`   Safe Pages: ${safePages.length}`);
  console.log(`   Sample Clicks: ${clicksData.length}`);
  console.log(`   Sample Conversions: ${conversionsCreated}`);
}

main()
  .catch((e) => {
    console.error('❌ Seeding failed:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
