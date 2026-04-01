/**
 * Mobile Operators Dictionary (Self-Contained TypeScript Data)
 * 
 * Mobile network operators by country with multilingual names (EN/RU).
 * This is a standalone TypeScript implementation that requires NO PHP dependencies.
 * 
 * Data originally derived from Keitaro TDS reference implementation.
 */

export interface OperatorInfo {
  key: string;       // Unique identifier
  country: string;   // ISO 3166-1 alpha-2 country code
  en: string;        // English name
  ru: string;        // Russian name
}

// Operators indexed by key
export const OPERATORS: Record<string, OperatorInfo> = {
  // A2 (Worldwide)
  'mtn_business_solutions_a2': { key: 'mtn_business_solutions_a2', country: 'A2', ru: 'MTN Business Solutions', en: 'MTN Business Solutions' },
  
  // AE - United Arab Emirates
  'telenor_ae': { key: 'telenor_ae', country: 'AE', ru: 'Telenor', en: 'Telenor' },
  'omanmobile_ae': { key: 'omanmobile_ae', country: 'AE', ru: 'OmanMobile', en: 'OmanMobile' },
  'du_ae': { key: 'du_ae', country: 'AE', ru: 'Du', en: 'Du' },
  
  // AF - Afghanistan
  'telecom_development_company_afghanistan_af': { key: 'telecom_development_company_afghanistan_af', country: 'AF', ru: 'Telecom Development Company Afghanistan', en: 'Telecom Development Company Afghanistan' },
  
  // AG - Antigua and Barbuda
  'digicel_ag': { key: 'digicel_ag', country: 'AG', ru: 'Digicel Albania', en: 'Digicel Albania' },
  
  // AL - Albania
  'telekom_albania_al': { key: 'telekom_albania_al', country: 'AL', ru: 'Telekom Albania', en: 'Telekom Albania' },
  'vodafone_al': { key: 'vodafone_al', country: 'AL', ru: 'Vodafone Albania', en: 'Vodafone Albania' },
  'plus_al': { key: 'plus_al', country: 'AL', ru: 'Plus', en: 'Plus' },
  'eagle_mobile_al': { key: 'eagle_mobile_al', country: 'AL', ru: 'Eagle Mobile', en: 'Eagle Mobile' },
  
  // AM - Armenia
  'beeline_am': { key: 'beeline_am', country: 'AM', ru: 'Билайн Армения', en: 'Beeline Armenia' },
  'orange_am': { key: 'orange_am', country: 'AM', ru: 'Orange Armenia', en: 'Orange Armenia' },
  'vivacell_am': { key: 'vivacell_am', country: 'AM', ru: 'VivaCell', en: 'VivaCell' },
  
  // AO - Angola
  'movicel_ao': { key: 'movicel_ao', country: 'AO', ru: 'Movicel', en: 'Movicel' },
  'multitel_ao': { key: 'multitel_ao', country: 'AO', ru: 'Multitel', en: 'Multitel' },
  
  // AR - Argentina
  'claro_ar': { key: 'claro_ar', country: 'AR', ru: 'Claro Argentina', en: 'Claro Argentina' },
  'personal_ar': { key: 'personal_ar', country: 'AR', ru: 'Personal', en: 'Personal' },
  'movistar_ar': { key: 'movistar_ar', country: 'AR', ru: 'Movistar Argentina', en: 'Movistar Argentina' },
  'nss_ar': { key: 'nss_ar', country: 'AR', ru: 'NSS', en: 'NSS' },
  
  // AT - Austria
  'a1_at': { key: 'a1_at', country: 'AT', ru: 'A1', en: 'A1' },
  'hutchison_3g_at': { key: 'hutchison_3g_at', country: 'AT', ru: 'Hutchison 3G', en: 'Hutchison 3G' },
  'vodafone_at': { key: 'vodafone_at', country: 'AT', ru: 'Vodafone', en: 'Vodafone' },
  't_mobile_at': { key: 't_mobile_at', country: 'AT', ru: 'T-Mobile Austria', en: 'T-Mobile Austria' },
  
  // AU - Australia
  'optus_au': { key: 'optus_au', country: 'AU', ru: 'Optus', en: 'Optus' },
  'vodafone_au': { key: 'vodafone_au', country: 'AU', ru: 'Vodafone Australia', en: 'Vodafone Australia' },
  'telstra_au': { key: 'telstra_au', country: 'AU', ru: 'Telstra', en: 'Telstra' },
  
  // AZ - Azerbaijan
  'nar_az': { key: 'nar_az', country: 'AZ', ru: 'Nar', en: 'Nar' },
  'bakcell_az': { key: 'bakcell_az', country: 'AZ', ru: 'Bakcell', en: 'Bakcell' },
  'azercell_az': { key: 'azercell_az', country: 'AZ', ru: 'Azercell', en: 'Azercell' },
  
  // BA - Bosnia and Herzegovina
  'bh_mobile_ba': { key: 'bh_mobile_ba', country: 'BA', ru: 'BH Mobile', en: 'BH Mobile' },
  'm_tel_ba': { key: 'm_tel_ba', country: 'BA', ru: 'm:tel', en: 'm:tel' },
  
  // BD - Bangladesh
  'grameenphone_bd': { key: 'grameenphone_bd', country: 'BD', ru: 'GrameenPhone', en: 'GrameenPhone' },
  'airtel_bd': { key: 'airtel_bd', country: 'BD', ru: 'Airtel Bangladesh', en: 'Airtel Bangladesh' },
  
  // BE - Belgium
  'proximus_be': { key: 'proximus_be', country: 'BE', ru: 'Proximus', en: 'Proximus' },
  'base_be': { key: 'base_be', country: 'BE', ru: 'BASE', en: 'BASE' },
  'orange_be': { key: 'orange_be', country: 'BE', ru: 'Orange Belgium', en: 'Orange Belgium' },
  
  // BG - Bulgaria
  'mobiltel_bg': { key: 'mobiltel_bg', country: 'BG', ru: 'Mobiltel', en: 'Mobiltel' },
  'telenor_bg': { key: 'telenor_bg', country: 'BG', ru: 'Telenor Bulgaria', en: 'Telenor Bulgaria' },
  'vivacom_bg': { key: 'vivacom_bg', country: 'BG', ru: 'Vivacom', en: 'Vivacom' },
  
  // BH - Bahrain
  'vodafone_bh': { key: 'vodafone_bh', country: 'BH', ru: 'Vodafone Bahrain', en: 'Vodafone Bahrain' },
  'batelco_bh': { key: 'batelco_bh', country: 'BH', ru: 'Batelco', en: 'Batelco' },
  
  // BR - Brazil
  'claro_br': { key: 'claro_br', country: 'BR', ru: 'Claro Brazil', en: 'Claro Brazil' },
  'vivo_br': { key: 'vivo_br', country: 'BR', ru: 'Vivo', en: 'Vivo' },
  'tim_br': { key: 'tim_br', country: 'BR', ru: 'Tim Brazil', en: 'Tim Brazil' },
  'oi_br': { key: 'oi_br', country: 'BR', ru: 'Oi', en: 'Oi' },
  
  // BY - Belarus
  'mts_by': { key: 'mts_by', country: 'BY', ru: 'МТС Беларусь', en: 'MTS Belarus' },
  'velcom_by': { key: 'velcom_by', country: 'BY', ru: 'Velcom', en: 'Velcom' },
  'life_by': { key: 'life_by', country: 'BY', ru: 'Life', en: 'Life' },
  
  // CA - Canada
  'bell_ca': { key: 'bell_ca', country: 'CA', ru: 'Bell', en: 'Bell' },
  'rogers_ca': { key: 'rogers_ca', country: 'CA', ru: 'Rogers', en: 'Rogers' },
  'telus_ca': { key: 'telus_ca', country: 'CA', ru: 'Telus', en: 'Telus' },
  'videotron_ca': { key: 'videotron_ca', country: 'CA', ru: 'Videotron', en: 'Videotron' },
  
  // CH - Switzerland
  'swisscom_ch': { key: 'swisscom_ch', country: 'CH', ru: 'Swisscom', en: 'Swisscom' },
  'sunrise_ch': { key: 'sunrise_ch', country: 'CH', ru: 'Sunrise', en: 'Sunrise' },
  'salt_ch': { key: 'salt_ch', country: 'CH', ru: 'Salt', en: 'Salt' },
  
  // CL - Chile
  'claro_cl': { key: 'claro_cl', country: 'CL', ru: 'Claro Chile', en: 'Claro Chile' },
  'movistar_cl': { key: 'movistar_cl', country: 'CL', ru: 'Movistar Chile', en: 'Movistar Chile' },
  'entel_cl': { key: 'entel_cl', country: 'CL', ru: 'Entel', en: 'Entel' },
  
  // CN - China
  'china_mobile_cn': { key: 'china_mobile_cn', country: 'CN', ru: 'China Mobile', en: 'China Mobile' },
  'china_unicom_cn': { key: 'china_unicom_cn', country: 'CN', ru: 'China Unicom', en: 'China Unicom' },
  'china_telecom_cn': { key: 'china_telecom_cn', country: 'CN', ru: 'China Telecom', en: 'China Telecom' },
  
  // CO - Colombia
  'claro_co': { key: 'claro_co', country: 'CO', ru: 'Claro Colombia', en: 'Claro Colombia' },
  'movistar_co': { key: 'movistar_co', country: 'CO', ru: 'Movistar Colombia', en: 'Movistar Colombia' },
  'tigo_co': { key: 'tigo_co', country: 'CO', ru: 'Tigo Colombia', en: 'Tigo Colombia' },
  
  // CZ - Czech Republic
  'o2_cz': { key: 'o2_cz', country: 'CZ', ru: 'O2 Czech Republic', en: 'O2 Czech Republic' },
  't_mobile_cz': { key: 't_mobile_cz', country: 'CZ', ru: 'T-Mobile Czech Republic', en: 'T-Mobile Czech Republic' },
  'vodafone_cz': { key: 'vodafone_cz', country: 'CZ', ru: 'Vodafone Czech Republic', en: 'Vodafone Czech Republic' },
  
  // DE - Germany
  'telekom_de': { key: 'telekom_de', country: 'DE', ru: 'Deutsche Telekom', en: 'Deutsche Telekom' },
  'vodafone_de': { key: 'vodafone_de', country: 'DE', ru: 'Vodafone Germany', en: 'Vodafone Germany' },
  'o2_de': { key: 'o2_de', country: 'DE', ru: 'O2 Germany', en: 'O2 Germany' },
  'e_plus_de': { key: 'e_plus_de', country: 'DE', ru: 'E-Plus', en: 'E-Plus' },
  
  // DK - Denmark
  'tdc_dk': { key: 'tdc_dk', country: 'DK', ru: 'TDC', en: 'TDC' },
  'telenor_dk': { key: 'telenor_dk', country: 'DK', ru: 'Telenor', en: 'Telenor' },
  'telia_dk': { key: 'telia_dk', country: 'DK', ru: 'Telia', en: 'Telia' },
  
  // EE - Estonia
  'elisa_ee': { key: 'elisa_ee', country: 'EE', ru: 'Elisa', en: 'Elisa' },
  'tele2_ee': { key: 'tele2_ee', country: 'EE', ru: 'Tele2 Estonia', en: 'Tele2 Estonia' },
  'telia_ee': { key: 'telia_ee', country: 'EE', ru: 'Telia Estonia', en: 'Telia Estonia' },
  
  // EG - Egypt
  'vodafone_eg': { key: 'vodafone_eg', country: 'EG', ru: 'Vodafone Egypt', en: 'Vodafone Egypt' },
  'orange_eg': { key: 'orange_eg', country: 'EG', ru: 'Orange Egypt', en: 'Orange Egypt' },
  'etisalat_eg': { key: 'etisalat_eg', country: 'EG', ru: 'Etisalat Egypt', en: 'Etisalat Egypt' },
  
  // ES - Spain
  'movistar_es': { key: 'movistar_es', country: 'ES', ru: 'Movistar Spain', en: 'Movistar Spain' },
  'vodafone_es': { key: 'vodafone_es', country: 'ES', ru: 'Vodafone Spain', en: 'Vodafone Spain' },
  'orange_es': { key: 'orange_es', country: 'ES', ru: 'Orange Spain', en: 'Orange Spain' },
  'yoigo_es': { key: 'yoigo_es', country: 'ES', ru: 'Yoigo', en: 'Yoigo' },
  
  // FI - Finland
  'elisa_fi': { key: 'elisa_fi', country: 'FI', ru: 'Elisa', en: 'Elisa' },
  'dna_fi': { key: 'dna_fi', country: 'FI', ru: 'DNA', en: 'DNA' },
  'telia_fi': { key: 'telia_fi', country: 'FI', ru: 'Telia Finland', en: 'Telia Finland' },
  
  // FR - France
  'orange_fr': { key: 'orange_fr', country: 'FR', ru: 'Orange France', en: 'Orange France' },
  'sfr_fr': { key: 'sfr_fr', country: 'FR', ru: 'SFR', en: 'SFR' },
  'bouygtel_fr': { key: 'bouygtel_fr', country: 'FR', ru: 'Bouygues Telecom', en: 'Bouygues Telecom' },
  'free_mobile_fr': { key: 'free_mobile_fr', country: 'FR', ru: 'Free Mobile', en: 'Free Mobile' },
  
  // GB - United Kingdom
  'ee_gb': { key: 'ee_gb', country: 'GB', ru: 'EE', en: 'EE' },
  'vodafone_gb': { key: 'vodafone_gb', country: 'GB', ru: 'Vodafone UK', en: 'Vodafone UK' },
  'o2_gb': { key: 'o2_gb', country: 'GB', ru: 'O2 UK', en: 'O2 UK' },
  'three_gb': { key: 'three_gb', country: 'GB', ru: 'Three UK', en: 'Three UK' },
  
  // GE - Georgia
  'geocell_ge': { key: 'geocell_ge', country: 'GE', ru: 'Geocell', en: 'Geocell' },
  'magticom_ge': { key: 'magticom_ge', country: 'GE', ru: 'MagtiCom', en: 'MagtiCom' },
  'beeline_ge': { key: 'beeline_ge', country: 'GE', ru: 'Билайн Грузия', en: 'Beeline Georgia' },
  
  // GR - Greece
  'cosmote_gr': { key: 'cosmote_gr', country: 'GR', ru: 'Cosmote', en: 'Cosmote' },
  'vodafone_gr': { key: 'vodafone_gr', country: 'GR', ru: 'Vodafone Greece', en: 'Vodafone Greece' },
  'wind_gr': { key: 'wind_gr', country: 'GR', ru: 'Wind', en: 'Wind' },
  
  // HK - Hong Kong
  'china_mobile_hk': { key: 'china_mobile_hk', country: 'HK', ru: 'China Mobile HK', en: 'China Mobile HK' },
  'csl_hk': { key: 'csl_hk', country: 'HK', ru: 'CSL', en: 'CSL' },
  'smartone_hk': { key: 'smartone_hk', country: 'HK', ru: 'SmarTone', en: 'SmarTone' },
  'three_hk': { key: 'three_hk', country: 'HK', ru: 'Three HK', en: 'Three HK' },
  
  // HR - Croatia
  'tele2_hr': { key: 'tele2_hr', country: 'HR', ru: 'Tele2 Croatia', en: 'Tele2 Croatia' },
  't_mobile_hr': { key: 't_mobile_hr', country: 'HR', ru: 'T-Mobile Croatia', en: 'T-Mobile Croatia' },
  'vip_hr': { key: 'vip_hr', country: 'HR', ru: 'VIP', en: 'VIP' },
  
  // HU - Hungary
  'telenor_hu': { key: 'telenor_hu', country: 'HU', ru: 'Telenor', en: 'Telenor' },
  'vodafone_hu': { key: 'vodafone_hu', country: 'HU', ru: 'Vodafone Hungary', en: 'Vodafone Hungary' },
  'telekom_hu': { key: 'telekom_hu', country: 'HU', ru: 'Telekom Hungary', en: 'Telekom Hungary' },
  
  // ID - Indonesia
  'telkomsel_id': { key: 'telkomsel_id', country: 'ID', ru: 'Telkomsel', en: 'Telkomsel' },
  'xl_axiata_id': { key: 'xl_axiata_id', country: 'ID', ru: 'XL Axiata', en: 'XL Axiata' },
  'indosat_id': { key: 'indosat_id', country: 'ID', ru: 'Indosat Ooredoo', en: 'Indosat Ooredoo' },
  'three_id': { key: 'three_id', country: 'ID', ru: 'Tri Indonesia', en: 'Tri Indonesia' },
  
  // IE - Ireland
  'vodafone_ie': { key: 'vodafone_ie', country: 'IE', ru: 'Vodafone Ireland', en: 'Vodafone Ireland' },
  'three_ie': { key: 'three_ie', country: 'IE', ru: 'Three Ireland', en: 'Three Ireland' },
  'eir_ie': { key: 'eir_ie', country: 'IE', ru: 'Eir', en: 'Eir' },
  
  // IL - Israel
  'cellcom_il': { key: 'cellcom_il', country: 'IL', ru: 'Cellcom Israel', en: 'Cellcom Israel' },
  'pelephone_il': { key: 'pelephone_il', country: 'IL', ru: 'Pelephone', en: 'Pelephone' },
  'hot_mobile_il': { key: 'hot_mobile_il', country: 'IL', ru: 'HOT Mobile', en: 'HOT Mobile' },
  
  // IN - India
  'airtel_in': { key: 'airtel_in', country: 'IN', ru: 'Airtel India', en: 'Airtel India' },
  'jio_in': { key: 'jio_in', country: 'IN', ru: 'Jio', en: 'Jio' },
  'vodafone_idea_in': { key: 'vodafone_idea_in', country: 'IN', ru: 'Vodafone Idea', en: 'Vodafone Idea' },
  
  // IT - Italy
  'tim_it': { key: 'tim_it', country: 'IT', ru: 'TIM', en: 'TIM' },
  'vodafone_it': { key: 'vodafone_it', country: 'IT', ru: 'Vodafone Italy', en: 'Vodafone Italy' },
  'wind_it': { key: 'wind_it', country: 'IT', ru: 'Wind', en: 'Wind' },
  'three_it': { key: 'three_it', country: 'IT', ru: 'Tre Italia', en: 'Tre Italia' },
  
  // JP - Japan
  'ntt_docomo_jp': { key: 'ntt_docomo_jp', country: 'JP', ru: 'NTT DoCoMo', en: 'NTT DoCoMo' },
  'kddi_jp': { key: 'kddi_jp', country: 'JP', ru: 'KDDI au', en: 'KDDI au' },
  'softbank_jp': { key: 'softbank_jp', country: 'JP', ru: 'SoftBank', en: 'SoftBank' },
  
  // KE - Kenya
  'safaricom_ke': { key: 'safaricom_ke', country: 'KE', ru: 'Safaricom', en: 'Safaricom' },
  'airtel_ke': { key: 'airtel_ke', country: 'KE', ru: 'Airtel Kenya', en: 'Airtel Kenya' },
  
  // KR - South Korea
  'sk_telecom_kr': { key: 'sk_telecom_kr', country: 'KR', ru: 'SK Telecom', en: 'SK Telecom' },
  'kt_kr': { key: 'kt_kr', country: 'KR', ru: 'KT', en: 'KT' },
  'lg_u_kr': { key: 'lg_u_kr', country: 'KR', ru: 'LG U+', en: 'LG U+' },
  
  // KZ - Kazakhstan
  'kcell_kz': { key: 'kcell_kz', country: 'KZ', ru: 'Kcell', en: 'Kcell' },
  'beeline_kz': { key: 'beeline_kz', country: 'KZ', ru: 'Билайн Казахстан', en: 'Beeline Kazakhstan' },
  'tele2_kz': { key: 'tele2_kz', country: 'KZ', ru: 'Tele2 Kazakhstan', en: 'Tele2 Kazakhstan' },
  
  // LV - Latvia
  'lmt_lv': { key: 'lmt_lv', country: 'LV', ru: 'LMT', en: 'LMT' },
  'tele2_lv': { key: 'tele2_lv', country: 'LV', ru: 'Tele2 Latvia', en: 'Tele2 Latvia' },
  'bite_lv': { key: 'bite_lv', country: 'LV', ru: 'Bite Latvia', en: 'Bite Latvia' },
  
  // LT - Lithuania
  'tele2_lt': { key: 'tele2_lt', country: 'LT', ru: 'Tele2 Lithuania', en: 'Tele2 Lithuania' },
  'bite_lt': { key: 'bite_lt', country: 'LT', ru: 'Bite Lithuania', en: 'Bite Lithuania' },
  'telia_lt': { key: 'telia_lt', country: 'LT', ru: 'Telia Lithuania', en: 'Telia Lithuania' },
  
  // MX - Mexico
  'telcel_mx': { key: 'telcel_mx', country: 'MX', ru: 'Telcel', en: 'Telcel' },
  'movistar_mx': { key: 'movistar_mx', country: 'MX', ru: 'Movistar Mexico', en: 'Movistar Mexico' },
  'at_t_mx': { key: 'at_t_mx', country: 'MX', ru: 'AT&T Mexico', en: 'AT&T Mexico' },
  
  // MY - Malaysia
  'maxis_my': { key: 'maxis_my', country: 'MY', ru: 'Maxis', en: 'Maxis' },
  'celcom_my': { key: 'celcom_my', country: 'MY', ru: 'Celcom', en: 'Celcom' },
  'digi_my': { key: 'digi_my', country: 'MY', ru: 'Digi', en: 'Digi' },
  
  // NL - Netherlands
  'kpn_nl': { key: 'kpn_nl', country: 'NL', ru: 'KPN', en: 'KPN' },
  'vodafone_nl': { key: 'vodafone_nl', country: 'NL', ru: 'Vodafone Netherlands', en: 'Vodafone Netherlands' },
  't_mobile_nl': { key: 't_mobile_nl', country: 'NL', ru: 'T-Mobile Netherlands', en: 'T-Mobile Netherlands' },
  
  // NO - Norway
  'telenor_no': { key: 'telenor_no', country: 'NO', ru: 'Telenor', en: 'Telenor' },
  'telia_no': { key: 'telia_no', country: 'NO', ru: 'Telia Norway', en: 'Telia Norway' },
  'ice_no': { key: 'ice_no', country: 'NO', ru: 'Ice', en: 'Ice' },
  
  // NZ - New Zealand
  'vodafone_nz': { key: 'vodafone_nz', country: 'NZ', ru: 'Vodafone NZ', en: 'Vodafone NZ' },
  'spark_nz': { key: 'spark_nz', country: 'NZ', ru: 'Spark', en: 'Spark' },
  'two_degrees_nz': { key: 'two_degrees_nz', country: 'NZ', ru: '2degrees', en: '2degrees' },
  
  // PE - Peru
  'claro_pe': { key: 'claro_pe', country: 'PE', ru: 'Claro Peru', en: 'Claro Peru' },
  'movistar_pe': { key: 'movistar_pe', country: 'PE', ru: 'Movistar Peru', en: 'Movistar Peru' },
  'entel_pe': { key: 'entel_pe', country: 'PE', ru: 'Entel Peru', en: 'Entel Peru' },
  
  // PH - Philippines
  'globe_ph': { key: 'globe_ph', country: 'PH', ru: 'Globe', en: 'Globe' },
  'smart_ph': { key: 'smart_ph', country: 'PH', ru: 'Smart', en: 'Smart' },
  
  // PK - Pakistan
  'jazz_pk': { key: 'jazz_pk', country: 'PK', ru: 'Jazz', en: 'Jazz' },
  'telenor_pk': { key: 'telenor_pk', country: 'PK', ru: 'Telenor Pakistan', en: 'Telenor Pakistan' },
  'zong_pk': { key: 'zong_pk', country: 'PK', ru: 'Zong', en: 'Zong' },
  
  // PL - Poland
  'orange_pl': { key: 'orange_pl', country: 'PL', ru: 'Orange Poland', en: 'Orange Poland' },
  't_mobile_pl': { key: 't_mobile_pl', country: 'PL', ru: 'T-Mobile Poland', en: 'T-Mobile Poland' },
  'play_pl': { key: 'play_pl', country: 'PL', ru: 'Play', en: 'Play' },
  'plus_pl': { key: 'plus_pl', country: 'PL', ru: 'Plus', en: 'Plus' },
  
  // PT - Portugal
  'meo_pt': { key: 'meo_pt', country: 'PT', ru: 'MEO', en: 'MEO' },
  'vodafone_pt': { key: 'vodafone_pt', country: 'PT', ru: 'Vodafone Portugal', en: 'Vodafone Portugal' },
  'nos_pt': { key: 'nos_pt', country: 'PT', ru: 'NOS', en: 'NOS' },
  
  // RO - Romania
  'orange_ro': { key: 'orange_ro', country: 'RO', ru: 'Orange Romania', en: 'Orange Romania' },
  'vodafone_ro': { key: 'vodafone_ro', country: 'RO', ru: 'Vodafone Romania', en: 'Vodafone Romania' },
  'telekom_ro': { key: 'telekom_ro', country: 'RO', ru: 'Telekom Romania', en: 'Telekom Romania' },
  
  // RU - Russia
  'mts_ru': { key: 'mts_ru', country: 'RU', ru: 'МТС', en: 'MTS' },
  'beeline_ru': { key: 'beeline_ru', country: 'RU', ru: 'Билайн', en: 'Beeline' },
  'megafon_ru': { key: 'megafon_ru', country: 'RU', ru: 'МегаФон', en: 'MegaFon' },
  'tele2_ru': { key: 'tele2_ru', country: 'RU', ru: 'Tele2 Russia', en: 'Tele2 Russia' },
  
  // SA - Saudi Arabia
  'stc_sa': { key: 'stc_sa', country: 'SA', ru: 'STC', en: 'STC' },
  'mobily_sa': { key: 'mobily_sa', country: 'SA', ru: 'Mobily', en: 'Mobily' },
  'zain_sa': { key: 'zain_sa', country: 'SA', ru: 'Zain Saudi', en: 'Zain Saudi' },
  
  // SE - Sweden
  'telia_se': { key: 'telia_se', country: 'SE', ru: 'Telia', en: 'Telia' },
  'tele2_se': { key: 'tele2_se', country: 'SE', ru: 'Tele2 Sweden', en: 'Tele2 Sweden' },
  'tre_se': { key: 'tre_se', country: 'SE', ru: 'Tre', en: 'Tre' },
  
  // SG - Singapore
  'singtel_sg': { key: 'singtel_sg', country: 'SG', ru: 'Singtel', en: 'Singtel' },
  'starhub_sg': { key: 'starhub_sg', country: 'SG', ru: 'StarHub', en: 'StarHub' },
  'm1_sg': { key: 'm1_sg', country: 'SG', ru: 'M1', en: 'M1' },
  
  // SK - Slovakia
  'orange_sk': { key: 'orange_sk', country: 'SK', ru: 'Orange Slovakia', en: 'Orange Slovakia' },
  'telekom_sk': { key: 'telekom_sk', country: 'SK', ru: 'Telekom Slovakia', en: 'Telekom Slovakia' },
  'o2_sk': { key: 'o2_sk', country: 'SK', ru: 'O2 Slovakia', en: 'O2 Slovakia' },
  
  // TH - Thailand
  'ais_th': { key: 'ais_th', country: 'TH', ru: 'AIS', en: 'AIS' },
  'dtac_th': { key: 'dtac_th', country: 'TH', ru: 'DTAC', en: 'DTAC' },
  'true_th': { key: 'true_th', country: 'TH', ru: 'True', en: 'True' },
  
  // TR - Turkey
  'turkcell_tr': { key: 'turkcell_tr', country: 'TR', ru: 'Turkcell', en: 'Turkcell' },
  'vodafone_tr': { key: 'vodafone_tr', country: 'TR', ru: 'Vodafone Turkey', en: 'Vodafone Turkey' },
  'turk_telekom_tr': { key: 'turk_telekom_tr', country: 'TR', ru: 'Türk Telekom', en: 'Türk Telekom' },
  
  // TW - Taiwan
  'cht_tw': { key: 'cht_tw', country: 'TW', ru: 'Chunghwa Telecom', en: 'Chunghwa Telecom' },
  'fareastone_tw': { key: 'fareastone_tw', country: 'TW', ru: 'FarEasTone', en: 'FarEasTone' },
  'taiwan_mobile_tw': { key: 'taiwan_mobile_tw', country: 'TW', ru: 'Taiwan Mobile', en: 'Taiwan Mobile' },
  
  // UA - Ukraine
  'kyivstar_ua': { key: 'kyivstar_ua', country: 'UA', ru: 'Киевстар', en: 'Kyivstar' },
  'vodafone_ua': { key: 'vodafone_ua', country: 'UA', ru: 'Vodafone Ukraine', en: 'Vodafone Ukraine' },
  'lifecell_ua': { key: 'lifecell_ua', country: 'UA', ru: 'lifecell', en: 'lifecell' },
  
  // US - United States
  'verizon_us': { key: 'verizon_us', country: 'US', ru: 'Verizon', en: 'Verizon' },
  'at_t_us': { key: 'at_t_us', country: 'US', ru: 'AT&T', en: 'AT&T' },
  't_mobile_us': { key: 't_mobile_us', country: 'US', ru: 'T-Mobile US', en: 'T-Mobile US' },
  'sprint_us': { key: 'sprint_us', country: 'US', ru: 'Sprint', en: 'Sprint' },
  
  // VN - Vietnam
  'viettel_vn': { key: 'viettel_vn', country: 'VN', ru: 'Viettel', en: 'Viettel' },
  'vinaphone_vn': { key: 'vinaphone_vn', country: 'VN', ru: 'Vinaphone', en: 'Vinaphone' },
  'mobifone_vn': { key: 'mobifone_vn', country: 'VN', ru: 'Mobifone', en: 'Mobifone' },
  
  // ZA - South Africa
  'vodacom_za': { key: 'vodacom_za', country: 'ZA', ru: 'Vodacom', en: 'Vodacom' },
  'mtn_za': { key: 'mtn_za', country: 'ZA', ru: 'MTN South Africa', en: 'MTN South Africa' },
  'cell_c_za': { key: 'cell_c_za', country: 'ZA', ru: 'Cell C', en: 'Cell C' },
};

/**
 * Get operators by country code
 */
export function getOperatorsByCountry(countryCode: string): OperatorInfo[] {
  return Object.values(OPERATORS).filter(op => op.country === countryCode.toUpperCase());
}

/**
 * Get operator by key
 */
export function getOperatorByKey(key: string): OperatorInfo | undefined {
  return OPERATORS[key.toLowerCase()];
}

/**
 * Get operator name
 */
export function getOperatorName(key: string, lang: 'en' | 'ru' = 'en'): string {
  const operator = OPERATORS[key.toLowerCase()];
  return operator ? operator[lang] : 'Unknown';
}

/**
 * Check if operator exists
 */
export function isValidOperator(key: string): boolean {
  return key.toLowerCase() in OPERATORS;
}

/**
 * Get all operator keys
 */
export function getOperatorKeys(): string[] {
  return Object.keys(OPERATORS);
}

/**
 * Get all country codes that have operators
 */
export function getCountriesWithOperators(): string[] {
  const countries = new Set<string>();
  Object.values(OPERATORS).forEach(op => countries.add(op.country));
  return Array.from(countries);
}
