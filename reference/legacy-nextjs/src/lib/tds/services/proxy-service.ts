/**
 * Proxy Detection Service
 * Detects if visitor is using proxy, VPN, or Tor
 * Based on Keitaro's proxy detection logic
 */

/**
 * Proxy Detection Result
 */
export interface ProxyDetectionResult {
  isProxy: boolean;
  isVpn: boolean;
  isTor: boolean;
  isHosting: boolean;
  isDataCenter: boolean;
  confidence: number; // 0-100
  proxyType: string | null;
  reasons: string[];
}

/**
 * Known proxy/vpn headers
 */
const PROXY_HEADERS = [
  'via',
  'x-forwarded-for',
  'x-proxy-id',
  'x-forwarded-host',
  'x-forwarded-server',
  'x-real-ip',
  'cf-connecting-ip',
  'true-client-ip',
  'x-originating-ip',
  'x-wap-profile',
  'front-end-https'
];

/**
 * Known VPN/proxy IP ranges (data center ranges)
 */
const DATACENTER_RANGES = [
  // AWS
  '52.0.0.0/8',
  '54.0.0.0/8',
  '3.0.0.0/8',
  // Google Cloud
  '35.0.0.0/8',
  '34.0.0.0/8',
  // DigitalOcean
  '167.99.0.0/16',
  '134.209.0.0/16',
  // Azure
  '13.0.0.0/8',
  '40.0.0.0/8',
  // Linode
  '45.33.0.0/16',
  // Vultr
  '45.77.0.0/16'
];

/**
 * Proxy Detection Service
 */
class ProxyService {
  private cache: Map<string, { data: ProxyDetectionResult; timestamp: number }> = new Map();
  private cacheTtl = 3600000; // 1 hour

  /**
   * Detect if IP is using proxy/VPN
   */
  async detectProxy(ip: string, headers: Record<string, string> = {}): Promise<ProxyDetectionResult> {
    // Check cache
    const cached = this.cache.get(ip);
    if (cached && Date.now() - cached.timestamp < this.cacheTtl) {
      return cached.data;
    }

    const result = await this.performDetection(ip, headers);
    
    // Cache result
    this.cache.set(ip, { data: result, timestamp: Date.now() });
    
    return result;
  }

  /**
   * Perform proxy detection
   */
  private async performDetection(ip: string, headers: Record<string, string>): Promise<ProxyDetectionResult> {
    const result: ProxyDetectionResult = {
      isProxy: false,
      isVpn: false,
      isTor: false,
      isHosting: false,
      isDataCenter: false,
      confidence: 0,
      proxyType: null,
      reasons: []
    };

    const reasons: string[] = [];
    let confidence = 0;

    // Check for proxy headers
    const headerScore = this.checkProxyHeaders(headers);
    if (headerScore > 0) {
      confidence += headerScore;
      reasons.push('proxy_headers_detected');
    }

    // Check for data center IP
    if (this.isDataCenterIp(ip)) {
      result.isDataCenter = true;
      result.isHosting = true;
      confidence += 40;
      reasons.push('datacenter_ip');
    }

    // Check for Tor exit node
    const isTor = await this.checkTorExitNode(ip);
    if (isTor) {
      result.isTor = true;
      confidence += 80;
      reasons.push('tor_exit_node');
    }

    // Check multiple forwarded IPs
    const forwardedIps = headers['x-forwarded-for'];
    if (forwardedIps && forwardedIps.split(',').length > 2) {
      confidence += 30;
      reasons.push('multiple_forwarded_ips');
    }

    // Check for VPN ports (common VPN ports in headers)
    const viaHeader = headers['via'];
    if (viaHeader) {
      if (viaHeader.includes('VPN') || viaHeader.includes('Proxy')) {
        confidence += 50;
        reasons.push('vpn_via_header');
      }
    }

    // Determine final result
    result.isProxy = confidence >= 50;
    result.isVpn = result.isDataCenter || (confidence >= 60 && !result.isTor);
    result.confidence = Math.min(confidence, 100);
    result.reasons = reasons;

    if (result.isTor) {
      result.proxyType = 'tor';
    } else if (result.isVpn) {
      result.proxyType = 'vpn';
    } else if (result.isProxy) {
      result.proxyType = 'proxy';
    }

    return result;
  }

  /**
   * Check for proxy headers
   */
  private checkProxyHeaders(headers: Record<string, string>): number {
    let score = 0;
    
    for (const header of PROXY_HEADERS) {
      const value = headers[header] || headers[header.toLowerCase()];
      if (value) {
        score += 10;
        
        // Check for suspicious values
        if (value.includes('proxy') || value.includes('vpn')) {
          score += 20;
        }
      }
    }
    
    return score;
  }

  /**
   * Check if IP is in data center range
   */
  private isDataCenterIp(ip: string): boolean {
    for (const range of DATACENTER_RANGES) {
      if (this.isIpInCidr(ip, range)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Check if IP is Tor exit node
   */
  private async checkTorExitNode(ip: string): Promise<boolean> {
    try {
      // Check against Tor exit node list
      // In production, use local database or cached list
      // For now, check via DNSBL
      const reversed = ip.split('.').reverse().join('.');
      const dnsblHost = `${reversed}.dnsbl.torproject.org`;
      
      // Simple DNS check (would need actual DNS resolution in production)
      // For development, return false
      return false;
    } catch {
      return false;
    }
  }

  /**
   * Check if IP is in CIDR range
   */
  private isIpInCidr(ip: string, cidr: string): boolean {
    const [range, bits] = cidr.split('/');
    const mask = parseInt(bits, 10);
    
    const ipNum = this.ipToNumber(ip);
    const rangeNum = this.ipToNumber(range);
    
    if (ipNum === null || rangeNum === null) return false;
    
    const maskNum = ~((1 << (32 - mask)) - 1);
    return (ipNum & maskNum) === (rangeNum & maskNum);
  }

  /**
   * Convert IP to number
   */
  private ipToNumber(ip: string): number | null {
    const parts = ip.split('.').map(p => parseInt(p, 10));
    if (parts.length !== 4 || parts.some(isNaN)) return null;
    return (parts[0] << 24) + (parts[1] << 16) + (parts[2] << 8) + parts[3];
  }

  /**
   * Clear cache
   */
  clearCache(): void {
    this.cache.clear();
  }
}

// Export singleton instance
export const proxyService = new ProxyService();
