/**
 * Operating Systems Dictionary (Self-Contained TypeScript Data)
 * 
 * List of known operating system names for device detection.
 * This is a standalone TypeScript implementation that requires NO PHP dependencies.
 * 
 * Data originally derived from Keitaro TDS reference implementation.
 */

export const OPERATING_SYSTEMS: string[] = [
  'AIX',
  'AmigaOS',
  'Android',
  'Apple TV',
  'Arch Linux',
  'BackTrack',
  'Bada',
  'BeOS',
  'BlackBerry OS',
  'BlackBerry Tablet OS',
  'Brew',
  'CentOS',
  'Chrome OS',
  'CyanogenMod',
  'Debian',
  'DragonFly',
  'Fedora',
  'Firefox OS',
  'FreeBSD',
  'GNU/Linux',
  'Gentoo',
  'Google TV',
  'HP-UX',
  'Haiku OS',
  'IRIX',
  'Inferno',
  'Knoppix',
  'Kubuntu',
  'Lubuntu',
  'MTK / Nucleus',
  'Maemo',
  'Mandriva',
  'MeeGo',
  'MildWild',
  'Mint',
  'MocorDroid',
  'MorphOS',
  'NetBSD',
  'Nintendo',
  'Nintendo Mobile',
  'OS/2',
  'OSF1',
  'OpenBSD',
  'PlayStation',
  'PlayStation Portable',
  'RISC OS',
  'RazoDroiD',
  'Red Hat',
  'Remix OS',
  'SUSE',
  'Sabayon',
  'Sailfish OS',
  'Slackware',
  'Solaris',
  'Syllable',
  'Symbian',
  'Symbian OS',
  'Symbian OS Series 40',
  'Symbian OS Series 60',
  'Symbian^3',
  'ThreadX',
  'Tizen',
  'Ubuntu',
  'VectorLinux',
  'WebTV',
  'Windows',
  'Windows CE',
  'Windows Mobile',
  'Windows Phone',
  'Windows RT',
  'Xbox',
  'Xubuntu',
  'YunOs',
  'iOS',
  'palmOS',
  'webOS',
  'OS X'
];

/**
 * Check if OS name is valid
 */
export function isValidOS(name: string): boolean {
  return OPERATING_SYSTEMS.includes(name);
}

/**
 * Get all OS names
 */
export function getOSNames(): string[] {
  return [...OPERATING_SYSTEMS];
}

/**
 * Get OS family
 */
export function getOSFamily(os: string): string {
  const families: Record<string, string> = {
    'Windows': 'Windows',
    'Windows CE': 'Windows',
    'Windows Mobile': 'Windows',
    'Windows Phone': 'Windows',
    'Windows RT': 'Windows',
    'Xbox': 'Windows',
    'OS X': 'Mac',
    'iOS': 'Mac',
    'iPhone OS': 'Mac',
    'Android': 'Linux',
    'Linux': 'Linux',
    'Ubuntu': 'Linux',
    'Debian': 'Linux',
    'Fedora': 'Linux',
    'CentOS': 'Linux',
    'Red Hat': 'Linux',
    'SUSE': 'Linux',
    'Arch Linux': 'Linux',
    'Gentoo': 'Linux',
    'Slackware': 'Linux',
    'Mint': 'Linux',
    'Kubuntu': 'Linux',
    'Lubuntu': 'Linux',
    'Xubuntu': 'Linux',
    'Chrome OS': 'Linux',
    'FreeBSD': 'BSD',
    'NetBSD': 'BSD',
    'OpenBSD': 'BSD'
  };
  
  return families[os] || 'Other';
}
