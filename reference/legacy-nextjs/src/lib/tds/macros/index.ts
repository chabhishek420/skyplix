/**
 * Macros System
 * Based on Keitaro TDS Macros Architecture
 * 
 * Supports both {macro_name} and $macro_name syntax
 * 
 * Examples:
 * - {subid} → click ID
 * - {country} → country code
 * - {ip} → visitor IP
 * - {keyword} → search keyword
 * - {random:16} → 16-char random string
 */

export * from './types';
export * from './processor';
export * from './registry';
