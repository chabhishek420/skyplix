/**
 * TDS Data Dictionary Exports
 * 
 * All static data is self-contained TypeScript implementation.
 * This module requires NO PHP dependencies.
 * 
 * Data originally derived from Keitaro TDS reference implementation.
 */

// Geo data
export { COUNTRIES, getCountryName, isValidCountryCode, getCountryCodes, type CountryInfo } from './countries';
export { CONNECTION_TYPES, getConnectionTypeName, isValidConnectionType, getMaxMindConnectionTypes, type ConnectionTypeInfo } from './connection-types';

// Device data
export { BROWSERS, isValidBrowser, getBrowserNames } from './browsers';
export { OPERATING_SYSTEMS, isValidOS, getOSNames, getOSFamily } from './operating-systems';
export { LANGUAGES, getLanguageName, isValidLanguageCode, getLanguageCodes, type LanguageInfo } from './languages';

// Referrer parsing
export { SEARCH_ENGINES, parseKeywordFromReferrer, getSearchEngineFromReferrer, isSearchEngineReferrer, type SearchEngineInfo } from './search-engines';

// Bot detection
export { BOT_SIGNATURES, ADDITIONAL_BOT_PATTERNS, getAllBotSignatures, isBotUserAgent, getMatchedBotSignature } from './bot-signatures';

// Mobile operators
export { OPERATORS, getOperatorsByCountry, getOperatorByKey, getOperatorName, isValidOperator, getOperatorKeys, getCountriesWithOperators, type OperatorInfo } from './operators';
