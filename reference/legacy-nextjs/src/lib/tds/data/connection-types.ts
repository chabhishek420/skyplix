/**
 * Connection Types Dictionary (Self-Contained TypeScript Data)
 * 
 * Network connection types with multilingual names (EN/RU).
 * This is a standalone TypeScript implementation that requires NO PHP dependencies.
 * 
 * Data originally derived from Keitaro TDS reference implementation.
 */

export interface ConnectionTypeInfo {
  en: string;
  ru: string;
  maxmind?: boolean;
}

export const CONNECTION_TYPES: Record<string, ConnectionTypeInfo> = {
  'Cellular': { ru: 'Сотовая связь', en: 'Cellular' },
  'Wifi': { ru: 'WiFi', en: 'WiFi' },
  'Dialup': { ru: 'Dial-up', en: 'Dial-up', maxmind: true },
  'Cable/DSL': { ru: 'Кабель/DSL', en: 'Cable/DSL', maxmind: true },
  'Corporate': { ru: 'Корпоративная сеть', en: 'Corporate', maxmind: true },
  '@empty': { ru: 'Неизвестно', en: 'Unknown' }
};

/**
 * Get connection type name
 */
export function getConnectionTypeName(type: string, lang: 'en' | 'ru' = 'en'): string {
  const connType = CONNECTION_TYPES[type];
  return connType ? connType[lang] : CONNECTION_TYPES['@empty'][lang];
}

/**
 * Check if connection type is valid
 */
export function isValidConnectionType(type: string): boolean {
  return type in CONNECTION_TYPES && type !== '@empty';
}

/**
 * Get connection types supported by MaxMind
 */
export function getMaxMindConnectionTypes(): string[] {
  return Object.entries(CONNECTION_TYPES)
    .filter(([_, info]) => info.maxmind)
    .map(([type]) => type);
}
