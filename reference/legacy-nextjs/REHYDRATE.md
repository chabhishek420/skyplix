# REHYDRATE.md — New Session Restore Guide

## Step 0: Restore your project files first

Before running any script, you need your project files in the workspace. Two options:

1. **Download + re-upload** — Use ZAI's "download workspace" to get a `.tar` of your current session. In the new session, upload that `.tar` and extract it.

2. **Git clone** — If you've pushed to GitHub:
   ```bash
   git clone https://github.com/himanshusharma930/zai-yt-keitaro.git .
   ```

## Step 1: Run the bootstrap

Once your files are in the workspace:

```bash
bash bootstrap.sh
```

This handles everything: node_modules, database, skills (cloned from git + installed), and verification.

## What bootstrap.sh does on a true cold start

| Phase | What happens | Needs internet? |
|-------|-------------|-----------------|
| 1 | Check bun, node, git, python3 exist | No |
| 2 | Create .env, Caddyfile, db/ if missing | No |
| 3 | Clone EZ Agents + Ralph Zero from GitHub, install deps | **Yes** |
| 4 | `bun install` (uses lockfile) | **Yes** |
| 5 | `bun run db:push` (Prisma + SQLite) | No |
| 6 | ESLint check + file structure verification | No |
| 7 | Print summary | No |

## If bootstrap.sh can't reach GitHub

Skills require network access. If the sandbox can't clone repos, run the manual fallback:

```bash
# Install project deps (no git needed)
bun install && bun run db:push

# Skills are optional — the project works without them
# EZ Agents: cd skills/ez-agents && bun install && bunx tsx bin/install.ts --local --claude
# Ralph Zero: cd skills/ralph-zero && python3 -m venv .venv && .venv/bin/pip install -e .
```

## What persists vs. what doesn't across sessions

| Persists (in project files) | Lost (session-specific) |
|---------------------------|----------------------|
| `src/`, `prisma/`, `public/` | `node_modules/` |
| `package.json`, `bun.lock` | `.next/` |
| `bootstrap.sh`, `CLAUDE.md` | `skills/*/node_modules/` |
| `Caddyfile`, `.env.example` | `skills/ralph-zero/.venv/` |
| `db/custom.db` (if in tar) | `.zscripts/` (platform-managed) |

## One-liner (after files are restored)

```bash
bash bootstrap.sh
```

That's it. Everything else is handled.
