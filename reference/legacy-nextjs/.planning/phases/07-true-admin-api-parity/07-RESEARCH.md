# Phase 7 Discovery: True Admin API Parity

## Overview
Phase 7 focuses on analyzing the remaining Keitaro admin controllers from `reference/Keitaro_source_php/application/Component/` and porting the relevant ones to `src/app/api/admin/` to achieve true 1:1 API capabilities.

## Codebase Analysis
By comparing Keitaro's ~58 `*Controller.php` files against our 18 Next.js API routes, the critical missing endpoints revolve around:

**1. Organizational & Metadata Primitives**
- `GroupsController.php`: Logical grouping for campaigns and items.
- `LabelsController.php`: Tagging systems used pervasively in the Keitaro UI.
- `TriggersController.php`: Automation hooks (e.g., pause campaign if ROI drops).

**2. Integrations & Templates**
- `FacebookController.php` / `AppsFlyerController.php`: Third-party integration APIs.
- `TrafficSourceTemplatesController.php`: Template definitions for quick TS setup.
- `AffiliateNetworkTemplatesController.php`: Template definitions for quick AN setup.
- `CodePresetsController.php`

**3. Stream Granularity**
- While we have `streams`, Keitaro separates `StreamActions`, `StreamEvents`, and `StreamFilters` into distinct controllers for granular UI manipulation.

## Technical Approach
Given aggressive atomicity, we will break this down into three sequential waves (plans):

**Wave 1: Organizational APIs** (Groups & Labels) - Fundamental for the UI to accurately filter and sort campaigns/offers.
**Wave 2: Automation & Triggers** (Triggers API) - Implementation of the trigger rules that act on metrics thresholds.
**Wave 3: Templates & Integrations** (Network Templates & FB/AppsFlyer) - Static data serving and third-party configuration objects.

## Context Preservation
This research proves we don't need to rebuild `Reports` or `BotLists` (as they already exist as `reports/route.ts` and `bot-rules/route.ts`), keeping us focused purely on the true delta between the original reference and our system.
