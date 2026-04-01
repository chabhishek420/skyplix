import { NextRequest, NextResponse } from 'next/server';
import { db } from '@/lib/db';
import { checkAuth } from '@/lib/auth';
import { z } from 'zod';

const AppsFlyerConfigSchema = z.object({
  devKey: z.string().optional(),
  appId: z.string().optional()
});

const SETTING_KEY_PREFIX = 'integration_appsflyer_';

// GET /api/admin/integrations/appsflyer - Get AppsFlyer integration config
export async function GET(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const settings = await db.setting.findMany({
      where: { key: { startsWith: SETTING_KEY_PREFIX } }
    });

    // Map KV to object
    const config: Record<string, string> = {
      devKey: '',
      appId: ''
    };

    settings.forEach(s => {
      const key = s.key.replace(SETTING_KEY_PREFIX, '');
      if (key in config) {
        config[key] = s.value;
      }
    });

    return NextResponse.json({ success: true, data: config });
  } catch (error) {
    console.error('Error fetching appsflyer config:', error);
    return NextResponse.json({ success: false, error: 'Failed to fetch appsflyer config' }, { status: 500 });
  }
}

// PUT /api/admin/integrations/appsflyer - Update AppsFlyer integration config
export async function PUT(request: NextRequest) {
  const authResponse = checkAuth(request);
  if (authResponse) return authResponse;
  
  try {
    const json = await request.json();
    const result = AppsFlyerConfigSchema.safeParse(json);
    
    if (!result.success) {
      return NextResponse.json({ success: false, error: 'Invalid input', details: result.error.format() }, { status: 400 });
    }

    // Upsert each key using SQLite transaction
    const operations = [];
    for (const [key, value] of Object.entries(result.data)) {
      if (value !== undefined) {
        operations.push(
          db.setting.upsert({
            where: { key: `${SETTING_KEY_PREFIX}${key}` },
            update: { value: value as string },
            create: { key: `${SETTING_KEY_PREFIX}${key}`, value: value as string, type: 'string' }
          })
        );
      }
    }

    await db.$transaction(operations);

    return NextResponse.json({ success: true, message: 'AppsFlyer configuration saved' });
  } catch (error: any) {
    console.error('Error updating appsflyer config:', error);
    return NextResponse.json({ success: false, error: 'Failed to update appsflyer config' }, { status: 500 });
  }
}
