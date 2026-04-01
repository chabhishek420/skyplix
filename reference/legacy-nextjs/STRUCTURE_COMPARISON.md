# Directory Structure Comparison: TypeScript vs PHP

This document provides a side-by-side comparison of the TypeScript implementation and the PHP reference source.

---

## TypeScript Implementation Structure (`/src/lib/tds/`)

```
src/lib/tds/
├── actions/
│   ├── predefined/
│   │   ├── blank-referrer.ts
│   │   ├── content.ts
│   │   ├── curl.ts
│   │   ├── do-nothing.ts
│   │   ├── double-meta.ts
│   │   ├── form-submit.ts
│   │   ├── frame.ts
│   │   ├── http-redirect.ts
│   │   ├── iframe.ts
│   │   ├── js.ts
│   │   ├── meta.ts
│   │   ├── remote.ts
│   │   ├── show-text.ts
│   │   ├── status404.ts
│   │   ├── subid.ts
│   │   └── to-campaign.ts
│   ├── base.ts
│   ├── index.ts
│   ├── repository.ts
│   └── types.ts
├── contexts/
│   ├── gateway-context.ts
│   ├── index.ts
│   └── landing-context.ts
├── filters/
│   ├── advanced.ts
│   ├── browser.ts
│   ├── connection.ts
│   ├── country.ts
│   ├── device-type.ts
│   ├── index.ts
│   ├── limit.ts
│   ├── os.ts
│   ├── registry.ts
│   ├── types.ts
│   └── uniqueness.ts
├── macros/
│   ├── predefined/
│   │   ├── advanced.ts
│   │   ├── browser.ts
│   │   ├── campaign.ts
│   │   ├── city.ts
│   │   ├── conversion.ts
│   │   ├── cost.ts
│   │   ├── country.ts
│   │   ├── datetime.ts
│   │   ├── device.ts
│   │   ├── geo.ts
│   │   ├── ip.ts
│   │   ├── keyword.ts
│   │   ├── landing.ts
│   │   ├── language.ts
│   │   ├── misc.ts
│   │   ├── offer.ts
│   │   ├── os.ts
│   │   ├── random.ts
│   │   ├── referrer.ts
│   │   ├── region.ts
│   │   ├── request.ts
│   │   ├── source.ts
│   │   ├── stream.ts
│   │   ├── subid.ts
│   │   ├── tracking.ts
│   │   └── user-agent.ts
│   ├── index.ts
│   ├── processor.ts
│   ├── registry.ts
│   └── types.ts
├── pipeline/
│   ├── stages/
│   │   ├── build-raw-click.ts
│   │   ├── check-bot.ts
│   │   ├── check-default-campaign.ts
│   │   ├── check-param-aliases.ts
│   │   ├── check-prefetch.ts
│   │   ├── check-sending-to-another-campaign.ts
│   │   ├── choose-landing.ts
│   │   ├── choose-offer.ts
│   │   ├── choose-stream.ts
│   │   ├── domain-redirect.ts
│   │   ├── execute-action.ts
│   │   ├── find-affiliate-network.ts
│   │   ├── find-campaign.ts
│   │   ├── generate-token.ts
│   │   ├── prepare-raw-click-to-store.ts
│   │   ├── save-uniqueness-session.ts
│   │   ├── set-cookie.ts
│   │   ├── store-raw-clicks.ts
│   │   ├── update-campaign-uniqueness.ts
│   │   ├── update-costs.ts
│   │   ├── update-hit-limit.ts
│   │   ├── update-params-from-landing.ts
│   │   ├── update-payout.ts
│   │   ├── update-raw-click.ts
│   │   └── update-stream-uniqueness.ts
│   ├── payload.ts
│   ├── pipeline.ts
│   └── types.ts
├── services/
│   ├── cookies-service.ts
│   ├── entity-binding-service.ts
│   ├── geo-db-service.ts
│   ├── index.ts
│   ├── ip-info-service.ts
│   ├── lp-token-service.ts
│   └── proxy-service.ts
├── utils/
│   ├── index.ts
│   └── raw-click-serializer.ts
├── bot-detection.ts
├── click-id.ts
├── click-processor.ts
├── index.ts
├── macros.ts
└── rotator.ts

Total: 11 directories, 107 files
```

---

## PHP Reference Structure (`/reference/application/`)

```
reference/application/
├── Admin/
│   ├── AdminApi/
│   ├── AdminRequest/
│   ├── Context/
│   ├── Controller/
│   └── Dispatcher/
├── Component/                    # 51 component modules
│   ├── AdminApi/
│   ├── AffiliateNetworks/
│   ├── Archive/
│   ├── Av/
│   ├── Benchmark/
│   ├── BotDetection/
│   │   ├── BotsStorage/
│   │   ├── ConsoleCommand/
│   │   ├── Controller/
│   │   ├── Model/
│   │   ├── PruneTask/
│   │   ├── Repository/
│   │   ├── Service/
│   │   └── CheckInList.php
│   ├── Branding/
│   ├── CampaignIntegration/
│   ├── Campaigns/
│   ├── Cleaner/
│   ├── Clicks/
│   │   ├── ClickProcessing/
│   │   ├── ConsoleCommand/
│   │   ├── Controller/
│   │   ├── CronTask/
│   │   ├── DelayedCommand/
│   │   ├── Grid/
│   │   ├── Model/
│   │   │   └── Ref/
│   │   ├── PruneTask/
│   │   ├── Repository/
│   │   └── Service/
│   ├── Common/
│   ├── Conversions/
│   ├── Cron/
│   ├── Dashboard/
│   ├── DelayedCommands/
│   ├── Device/
│   ├── Diagnostics/
│   ├── Domains/
│   ├── Editor/
│   ├── EntityGrid/
│   ├── GeoDb/                    # GeoIP databases
│   │   ├── Adapter/
│   │   ├── ConsoleCommand/
│   │   ├── Controller/
│   │   ├── DownloadManager/
│   │   ├── Ip2Location/
│   │   ├── Keitaro/
│   │   ├── Maxmind/
│   │   ├── ProIP/
│   │   ├── Sypex/
│   │   └── Repository/
│   ├── GeoProfiles/
│   ├── Grid/
│   ├── Groups/
│   ├── Home/
│   ├── Landings/
│   ├── Logs/
│   ├── Macros/
│   ├── Migrations/
│   ├── Offers/
│   ├── Postback/
│   │   ├── Controller/
│   │   ├── DelayedCommand/
│   │   ├── ProcessPostback/
│   │   │   └── Stages/
│   │   └── Postback.php
│   ├── PruneTask/
│   ├── Reports/
│   ├── SelfUpdate/
│   ├── Settings/
│   ├── Simulation/
│   ├── Stats/
│   ├── StreamActions/
│   ├── StreamEvents/
│   ├── StreamFilters/            # Stream filter system
│   │   ├── Filter/
│   │   │   ├── AnyParam.php
│   │   │   ├── Browser.php
│   │   │   ├── BrowserVersion.php
│   │   │   ├── City.php
│   │   │   ├── ConnectionType.php
│   │   │   ├── Country.php
│   │   │   ├── DeviceModel.php
│   │   │   ├── DeviceType.php
│   │   │   ├── EmptyReferrer.php
│   │   │   ├── HideClickDetect.php
│   │   │   ├── Interval.php
│   │   │   ├── Ip.php
│   │   │   ├── Ipv6.php
│   │   │   ├── IsBot.php
│   │   │   ├── Isp.php
│   │   │   ├── Language.php
│   │   │   ├── Limit.php
│   │   │   ├── Operator.php
│   │   │   ├── Os.php
│   │   │   ├── OsVersion.php
│   │   │   ├── Parameter.php
│   │   │   ├── Proxy.php
│   │   │   ├── Referrer.php
│   │   │   ├── Region.php
│   │   │   ├── Schedule.php
│   │   │   ├── Uniqueness.php
│   │   │   └── UserAgent.php
│   │   ├── CheckFilters.php
│   │   └── VersionMatcher.php
│   ├── Streams/
│   ├── System/
│   ├── Templates/
│   ├── ThirdPartyIntegration/
│   ├── TrafficSources/
│   ├── Trends/
│   ├── Triggers/
│   └── Users/
├── Core/                         # Core framework
│   ├── Application/
│   ├── Component/
│   ├── Db/
│   ├── Entity/
│   ├── Filter/
│   ├── Kernel/
│   ├── Model/
│   ├── Router/
│   └── Validator/
├── Cron/
├── Traffic/                      # Core TDS logic
│   ├── Actions/
│   │   ├── Predefined/
│   │   │   ├── BlankReferrer.php
│   │   │   ├── Curl.php
│   │   │   ├── DoNothing.php
│   │   │   ├── DoubleMeta.php
│   │   │   ├── FormSubmit.php
│   │   │   ├── Frame.php
│   │   │   ├── HttpRedirect.php
│   │   │   ├── Iframe.php
│   │   │   ├── Js.php
│   │   │   ├── JsForIframe.php
│   │   │   ├── JsForScript.php
│   │   │   ├── LocalFile.php
│   │   │   ├── Meta.php
│   │   │   ├── Remote.php
│   │   │   ├── ShowHtml.php
│   │   │   ├── ShowText.php
│   │   │   ├── Status404.php
│   │   │   ├── SubId.php
│   │   │   └── ToCampaign.php
│   │   ├── AbstractAction.php
│   │   ├── ActionError.php
│   │   └── CurlService.php
│   ├── Cache/
│   ├── CachedData/
│   ├── Context/
│   ├── Cookies/
│   ├── Device/
│   ├── GeoDb/
│   │   ├── GeoDbService.php
│   │   └── IpInfoService.php
│   ├── HitLimit/
│   ├── Http/
│   ├── LpToken/
│   ├── Macros/
│   │   ├── Predefined/
│   │   ├── AbstractMacro.php
│   │   ├── MacroRepository.php
│   │   └── MacrosProcessor.php
│   ├── Model/
│   ├── Pipeline/
│   │   ├── Stage/
│   │   │   ├── BuildRawClickStage.php
│   │   │   ├── CheckDefaultCampaignStage.php
│   │   │   ├── CheckParamAliasesStage.php
│   │   │   ├── CheckPrefetchStage.php
│   │   │   ├── CheckSendingToAnotherCampaign.php
│   │   │   ├── ChooseLandingStage.php
│   │   │   ├── ChooseOfferStage.php
│   │   │   ├── ChooseStreamStage.php
│   │   │   ├── DomainRedirectStage.php
│   │   │   ├── ExecuteActionStage.php
│   │   │   ├── FindAffiliateNetworkStage.php
│   │   │   ├── FindCampaignStage.php
│   │   │   ├── GenerateTokenStage.php
│   │   │   ├── PrepareRawClickToStoreStage.php
│   │   │   ├── SaveUniquenessSessionStage.php
│   │   │   ├── SetCookieStage.php
│   │   │   ├── StoreRawClicksStage.php
│   │   │   ├── UpdateCampaignUniquenessSessionStage.php
│   │   │   ├── UpdateCostsStage.php
│   │   │   ├── UpdateHitLimitStage.php
│   │   │   ├── UpdateParamsFromLandingStage.php
│   │   │   ├── UpdatePayoutStage.php
│   │   │   ├── UpdateRawClickStage.php
│   │   │   └── UpdateStreamUniquenessSessionStage.php
│   │   ├── Payload.php
│   │   └── Pipeline.php
│   ├── Repository/
│   ├── Response/
│   ├── Service/
│   │   ├── IpInfoService.php
│   │   └── VisitorBindingService.php
│   ├── Session/
│   ├── RawClick.php
│   └── RawClickInterface.php
├── config/
├── filters/
├── macros/
├── migrations/
└── migrations2/

Total: 3,119 PHP files
```

---

## Key File Mapping

| TypeScript | PHP | Purpose |
|------------|-----|---------|
| `pipeline/pipeline.ts` | `Traffic/Pipeline/Pipeline.php` | Pipeline orchestrator |
| `pipeline/payload.ts` | `Traffic/Pipeline/Payload.php` | Pipeline payload container |
| `pipeline/stages/*.ts` | `Traffic/Pipeline/Stage/*.php` | 28 pipeline stages |
| `actions/base.ts` | `Traffic/Actions/AbstractAction.php` | Action base class |
| `actions/predefined/*.ts` | `Traffic/Actions/Predefined/*.php` | 19 action types |
| `filters/index.ts` | `Component/StreamFilters/CheckFilters.php` | Filter checker |
| `filters/*.ts` | `Component/StreamFilters/Filter/*.php` | 24 filter types |
| `macros/processor.ts` | `Traffic/Macros/MacrosProcessor.php` | Macro processor |
| `services/geo-db-service.ts` | `Traffic/GeoDb/GeoDbService.php` | GeoIP service |
| `services/ip-info-service.ts` | `Traffic/Service/IpInfoService.php` | IP info service |
| `services/entity-binding-service.ts` | `Traffic/Service/VisitorBindingService.php` | Visitor binding |
| `bot-detection.ts` | `Component/BotDetection/` | Bot detection system |
| `pipeline/types.ts` | `Traffic/RawClick.php` | RawClick data model |
| `rotator.ts` | `Traffic/Pipeline/Rotator/StreamRotator.php` | Stream selection |
| `click-id.ts` | `Traffic/RawClick.php` | Click ID generation |

---

## Pipeline Stages Comparison

### First Level Stages (23 stages)

| # | PHP Stage | TypeScript Stage |
|---|-----------|------------------|
| 1 | DomainRedirectStage | domain-redirect.ts |
| 2 | CheckPrefetchStage | check-prefetch.ts |
| 3 | BuildRawClickStage | build-raw-click.ts |
| 4 | FindCampaignStage | find-campaign.ts |
| 5 | CheckDefaultCampaignStage | check-default-campaign.ts |
| 6 | UpdateRawClickStage | update-raw-click.ts |
| 7 | CheckParamAliasesStage | check-param-aliases.ts |
| 8 | UpdateCampaignUniquenessSessionStage | update-campaign-uniqueness.ts |
| 9 | ChooseStreamStage | choose-stream.ts |
| 10 | UpdateStreamUniquenessSessionStage | update-stream-uniqueness.ts |
| 11 | ChooseLandingStage | choose-landing.ts |
| 12 | ChooseOfferStage | choose-offer.ts |
| 13 | GenerateTokenStage | generate-token.ts |
| 14 | FindAffiliateNetworkStage | find-affiliate-network.ts |
| 15 | UpdateHitLimitStage | update-hit-limit.ts |
| 16 | UpdateCostsStage | update-costs.ts |
| 17 | UpdatePayoutStage | update-payout.ts |
| 18 | SaveUniquenessSessionStage | save-uniqueness-session.ts |
| 19 | SetCookieStage | set-cookie.ts |
| 20 | ExecuteActionStage | execute-action.ts |
| 21 | PrepareRawClickToStoreStage | prepare-raw-click-to-store.ts |
| 22 | CheckSendingToAnotherCampaign | check-sending-to-another-campaign.ts |
| 23 | StoreRawClicksStage | store-raw-clicks.ts |

### Second Level Stages (13 stages) - LP→Offer Flow

| # | PHP Stage | TypeScript Stage |
|---|-----------|------------------|
| 1 | FindCampaignStage | find-campaign.ts |
| 2 | UpdateParamsFromLandingStage | update-params-from-landing.ts |
| 3 | CheckDefaultCampaignStage | check-default-campaign.ts |
| 4 | CheckParamAliasesStage | check-param-aliases.ts |
| 5 | ChooseStreamStage | choose-stream.ts |
| 6 | ChooseOfferStage | choose-offer.ts |
| 7 | FindAffiliateNetworkStage | find-affiliate-network.ts |
| 8 | UpdateCostsStage | update-costs.ts |
| 9 | UpdatePayoutStage | update-payout.ts |
| 10 | SetCookieStage | set-cookie.ts |
| 11 | ExecuteActionStage | execute-action.ts |
| 12 | CheckSendingToAnotherCampaign | check-sending-to-another-campaign.ts |
| 13 | StoreRawClicksStage | store-raw-clicks.ts |

---

## Action Types Comparison (19 types)

| PHP | TypeScript | Description |
|-----|------------|-------------|
| HttpRedirect | http-redirect.ts | HTTP 302 redirect |
| Meta | meta.ts | Meta refresh redirect |
| DoubleMeta | double-meta.ts | Double meta (referrer hiding) |
| Iframe | iframe.ts | IFrame embed |
| Frame | frame.ts | Frameset embed |
| Js | js.ts | JavaScript redirect |
| ShowHtml | content.ts | Show HTML content |
| ShowText | show-text.ts | Show plain text |
| Status404 | status404.ts | Return 404 error |
| DoNothing | do-nothing.ts | Empty response |
| Curl | curl.ts | cURL request |
| Remote | remote.ts | Remote URL fetch |
| FormSubmit | form-submit.ts | Auto-submit form |
| LocalFile | - (not implemented) | Serve local file |
| BlankReferrer | blank-referrer.ts | Hide referrer |
| ToCampaign | to-campaign.ts | Redirect to campaign |
| SubId | subid.ts | Generate sub ID |
| JsForIframe | - (merged into js.ts) | JS for iframe |
| JsForScript | - (merged into js.ts) | JS for script |

---

## Filter Types Comparison (24 types)

| PHP Filter | TypeScript Filter | Description |
|------------|-------------------|-------------|
| Country | CountryFilter | Country geo-targeting |
| Region | RegionFilter | Region/state targeting |
| City | CityFilter | City targeting |
| Browser | BrowserFilter | Browser name filter |
| BrowserVersion | BrowserVersionFilter | Browser version filter |
| Os | OsFilter | OS filter |
| OsVersion | OsVersionFilter | OS version filter |
| DeviceType | DeviceTypeFilter | Desktop/mobile/tablet |
| DeviceModel | DeviceModelFilter | Device model filter |
| Ip | IpFilter | IP with CIDR support |
| Ipv6 | Ipv6Filter | IPv6 filtering |
| Language | LanguageFilter | Browser language |
| Referrer | ReferrerFilter | Referrer URL |
| EmptyReferrer | EmptyReferrerFilter | Empty referrer check |
| Keyword | KeywordFilter | Keyword matching |
| Schedule | ScheduleFilter | Time-based filter |
| IsBot | IsBotFilter | Bot status |
| Proxy | ProxyFilter | Proxy detection |
| ConnectionType | ConnectionTypeFilter | Connection type |
| Isp | IspFilter | ISP filter |
| Operator | OperatorFilter | Mobile operator |
| Limit | LimitFilter | Click rate limiting |
| Uniqueness | UniquenessFilter | Visitor uniqueness |
| Interval | IntervalFilter | Time distribution |
| Parameter | ParameterFilter | URL parameter |
| AnyParam | AnyParamFilter | Any parameter |
| UserAgent | UserAgentFilter | UA pattern matching |
| HideClickDetect | HideClickDetectFilter | Anti-detect |

---

## Statistics

| Metric | TypeScript | PHP |
|--------|------------|-----|
| Total Files | 107 | 3,119 |
| Pipeline Stages | 26 | 28 |
| Action Types | 16 | 19 |
| Filter Types | 24 | 24 |
| Macro Files | 27 | 50+ |
| Services | 7 | 20+ |

---

## Integration Status

| Component | TS Exists | PHP Reference | Integration Needed |
|-----------|-----------|---------------|-------------------|
| Pipeline | ✅ | `Traffic/Pipeline/` | Compare algorithms |
| Actions | ✅ | `Traffic/Actions/` | Compare implementations |
| Filters | ✅ | `Component/StreamFilters/` | Compare filter logic |
| Macros | ✅ | `Traffic/Macros/` | Compare macro list |
| Services | ✅ | `Traffic/Service/` | Wire to pipeline |
| GeoIP | ✅ | `Traffic/GeoDb/` | Integrate MaxMind |
| Bot Detection | ✅ | `Component/BotDetection/` | Compare signatures |

---

*Generated: 2025-03-29*
*PHP Source: Keitaro TDS (reference/)*
*TypeScript Implementation: src/lib/tds/*
