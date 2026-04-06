# Keitaro to SkyPlix Migration Guide

SkyPlix provides a direct CLI tool to import your existing campaigns, offers, and streams from Keitaro (PHP) into SkyPlix.

## Step 1: Preparation

1. **Backup Your Data**: Ensure you have a full backup of your Keitaro MySQL database.
2. **Access Requirements**: The SkyPlix server must have read access to the Keitaro MySQL instance.
3. **Workspace Configuration**: Identify the SkyPlix Workspace ID (UUID) where the imported entities will be placed.

## Step 2: The Migration Command

Use the `migrate keitaro` subcommand:

```bash
./skyplix migrate keitaro \
  --source-db "user:password@tcp(mysql-host:3306)/keitaro_db" \
  --workspace-id "00000000-0000-4000-a000-000000000001" \
  --dry-run
```

### Flags

- `--source-db`: Standard MySQL connection string for the source Keitaro DB.
- `--workspace-id`: The target SkyPlix workspace.
- `--dry-run`: (Optional) Validates the migration and reports potential issues without writing to SkyPlix PostgreSQL.

## Step 3: Mapping logic

SkyPlix automatically maps the following entities:

- **Campaigns**: Name, Alias, Type, State, Cost Model, and Value.
- **Streams**: Type (Forced/Regular/Default), Position, Weight, and Filter Logic.
- **Offers**: URL, Payout, Daily Cap, and State.
- **Filters**: Maps Keitaro's filter types to SkyPlix's internal filter engine.

## Step 4: Verification

1. Log into the SkyPlix Admin UI.
2. Verify that all campaigns are present in the designated workspace.
3. Run a test click using a campaign's Alias URL.
4. Check the "Clicks" log in the Admin UI to confirm tracking is functional.
