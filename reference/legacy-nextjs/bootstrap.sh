#!/usr/bin/env bash
# ============================================================================
# bootstrap.sh — ZAI Session Rehydration Script
# ============================================================================
# Restores the full development environment at the start of every new session.
# Run from your project root:  bash bootstrap.sh
#
# What it does:
#   Phase 1: Validates system prerequisites (bun, node, python3, git)
#   Phase 2: Creates runtime configs (.env, Caddyfile, db dir)
#   Phase 3: Clones and installs skills (EZ Agents, Ralph Zero) from git
#   Phase 4: Installs Node.js dependencies (bun install)
#   Phase 5: Sets up Prisma + SQLite database
#   Phase 6: Verifies lint passes and essential files exist
#   Phase 7: Prints session-ready summary
#
# IMPORTANT: .zscripts/ is managed by the ZAI platform — this script will NOT
# overwrite it. The Caddyfile is also platform-managed and left alone if present.
#
# Version-controlled: commit this file alongside your project.
# ============================================================================

set -euo pipefail

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
DIM='\033[2m'
BOLD='\033[1m'
NC='\033[0m'

# ── Project root detection ───────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"
cd "$PROJECT_DIR"

log()  { echo -e "${CYAN}[bootstrap]${NC} $*"; }
ok()   { echo -e "${GREEN}  ✓${NC} $*"; }
warn() { echo -e "${YELLOW}  ⚠${NC} $*"; }
fail() { echo -e "${RED}  ✗${NC} $*"; }

step() {
  echo ""
  echo -e "${BOLD}${CYAN}━━━ $1 ━━━${NC}"
  echo ""
}

time_now() { date '+%Y-%m-%d %H:%M:%S'; }

# ── Configurable skill repos (edit URLs here to pin versions) ────────────────
EZ_AGENTS_REPO="https://github.com/howlil/ez-agents.git"
RALPH_ZERO_REPO="https://github.com/himanshusharma930/ralph-zero.git"

# ── Phase 0: Banner ─────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}  ╔══════════════════════════════════════════╗${NC}"
echo -e "${BOLD}  ║     ZAI Session Rehydration Script       ║${NC}"
echo -e "${BOLD}  ║     Project: zai-yt-keitaro (TDS)       ║${NC}"
echo -e "${BOLD}  ╚══════════════════════════════════════════╝${NC}"
echo -e "  ${DIM}Started at $(time_now)${NC}"
echo -e "  ${DIM}Project dir: ${PROJECT_DIR}${NC}"

BOOT_START=$(date +%s)

# ── Phase 1: Prerequisites ─────────────────────────────────────────────────
step "Phase 1/7 — System Prerequisites"

MISSING=()

check_cmd() {
  if command -v "$1" &>/dev/null; then
    local ver
    ver=$("$1" --version 2>&1 | head -1)
    ok "$1 — ${ver}"
  else
    fail "$1 — NOT FOUND"
    MISSING+=("$1")
  fi
}

check_cmd "bun"
check_cmd "node"
check_cmd "git"
check_cmd "python3"

if [ ${#MISSING[@]} -ne 0 ]; then
  echo ""
  fail "Missing tools: ${MISSING[*]}"
  fail "Cannot continue. Please install missing tools and retry."
  exit 1
fi

# ── Phase 2: Runtime Configs ────────────────────────────────────────────────
step "Phase 2/7 — Runtime Configurations"

# .env — only create if missing (never overwrite)
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    cp .env.example .env
    ok "Created .env from .env.example"
  else
    cat > .env << 'ENVEOF'
DATABASE_URL="file:./db/custom.db"
ENVEOF
    ok "Created .env (default)"
  fi
else
  ok "Found .env"
fi

# Ensure db directory exists
mkdir -p db

# .zscripts/ — managed by ZAI platform, do NOT create or overwrite
if [ -d .zscripts ]; then
  ok "Found .zscripts/ (platform-managed)"
else
  warn ".zscripts/ not found — the ZAI platform usually creates this"
fi

# Caddyfile — managed by ZAI platform, only create if missing
if [ -f Caddyfile ]; then
  ok "Found Caddyfile (platform-managed)"
else
  cat > Caddyfile << 'EOF'
:81 {
	@transform_port_query {
		query XTransformPort=*
	}

	handle @transform_port_query {
		reverse_proxy localhost:{query.XTransformPort} {
			header_up Host {host}
			header_up X-Forwarded-For {remote_host}
			header_up X-Forwarded-Proto {scheme}
			header_up X-Real-IP {remote_host}
		}
	}

	handle {
		reverse_proxy localhost:3000 {
			header_up Host {host}
			header_up X-Forwarded-For {remote_host}
			header_up X-Forwarded-Proto {scheme}
			header_up X-Real-IP {remote_host}
		}
	}
}
EOF
  ok "Created Caddyfile (default gateway config)"
fi

# ── Phase 3: Skills — Clone + Install ───────────────────────────────────────
step "Phase 3/7 — Skills (clone from git + install)"

mkdir -p skills

# ── EZ Agents ──
if [ ! -d skills/ez-agents ]; then
  log "Cloning EZ Agents from ${EZ_AGENTS_REPO} ..."
  git clone --depth 1 "${EZ_AGENTS_REPO}" skills/ez-agents 2>&1 | tail -3
  # Remove .git to save space (we control version via URL)
  rm -rf skills/ez-agents/.git
  ok "EZ Agents cloned"
else
  ok "EZ Agents directory exists"
fi

if [ ! -d skills/ez-agents/node_modules ]; then
  log "Installing EZ Agents dependencies..."
  (cd skills/ez-agents && bun install 2>&1 | tail -3)
  ok "EZ Agents dependencies installed"
else
  ok "EZ Agents node_modules exists"
fi

if [ ! -d skills/ez-agents/.claude ]; then
  log "Running EZ Agents Claude installer..."
  (cd skills/ez-agents && bunx tsx bin/install.ts --local --claude 2>&1 | tail -5)
  ok "EZ Agents .claude/ configured"
else
  ok "EZ Agents .claude/ already configured"
fi

# ── Ralph Zero ──
if [ ! -d skills/ralph-zero ]; then
  log "Cloning Ralph Zero from ${RALPH_ZERO_REPO} ..."
  git clone --depth 1 "${RALPH_ZERO_REPO}" skills/ralph-zero 2>&1 | tail -3
  rm -rf skills/ralph-zero/.git
  ok "Ralph Zero cloned"
else
  ok "Ralph Zero directory exists"
fi

if [ ! -d skills/ralph-zero/.venv ]; then
  log "Creating Ralph Zero Python venv..."
  (cd skills/ralph-zero && python3 -m venv .venv 2>&1)
  (cd skills/ralph-zero && .venv/bin/pip install -e . 2>&1 | tail -3)
  ok "Ralph Zero installed (.venv)"
else
  ok "Ralph Zero .venv exists"
  # Verify CLI actually works
  if (cd skills/ralph-zero && .venv/bin/ralph-zero --version &>/dev/null); then
    ok "Ralph Zero CLI verified"
  else
    warn "Ralph Zero CLI broken — reinstalling..."
    (cd skills/ralph-zero && .venv/bin/pip install -e . 2>&1 | tail -3)
    ok "Ralph Zero reinstalled"
  fi
fi

# ── Phase 4: Node.js Dependencies ──────────────────────────────────────────
step "Phase 4/7 — Node.js Dependencies (bun install)"

if [ -f bun.lock ] || [ -f package-lock.json ]; then
  if [ ! -d node_modules ]; then
    log "Installing dependencies..."
    bun install 2>&1 | tail -5
    ok "Dependencies installed"
  else
    ok "node_modules exists — skipping (rm -rf node_modules to force)"
  fi
else
  warn "No lockfile found — running fresh install"
  bun install 2>&1 | tail -5
  ok "Dependencies installed"
fi

# ── Phase 5: Database Setup ────────────────────────────────────────────────
step "Phase 5/7 — Prisma & SQLite Database"

if [ -f prisma/schema.prisma ]; then
  log "Syncing Prisma schema to database..."
  bun run db:push 2>&1 | tail -5
  ok "Database synced"
else
  warn "No prisma/schema.prisma found — skipping DB setup"
fi

# ── Phase 6: Verification ──────────────────────────────────────────────────
step "Phase 6/7 — Verification"

# Lint check
log "Running ESLint..."
if bun run lint 2>&1; then
  ok "Lint passed"
else
  warn "Lint has issues (non-blocking)"
fi

# Essential file structure
ESSENTIAL_FILES=(
  "package.json"
  "prisma/schema.prisma"
  "src/app/page.tsx"
  "src/app/layout.tsx"
  ".env"
  "Caddyfile"
)

for f in "${ESSENTIAL_FILES[@]}"; do
  if [ -f "$f" ]; then
    ok "$f"
  else
    warn "$f MISSING"
  fi
done

# ── Phase 7: Summary ───────────────────────────────────────────────────────
BOOT_END=$(date +%s)
BOOT_DURATION=$((BOOT_END - BOOT_START))

step "Phase 7/7 — Session Ready!"

echo -e "${GREEN}  Environment rehydrated successfully in ${BOOT_DURATION}s${NC}"
echo ""
echo -e "${BOLD}  Quick Reference:${NC}"
echo -e "  ${DIM}Project:      ${PROJECT_DIR}${NC}"
echo -e "  ${DIM}Dev server:   bun run dev (auto-started on port 3000)${NC}"
echo -e "  ${DIM}DB:           SQLite at db/custom.db${NC}"
echo -e "  ${DIM}Lint:         bun run lint${NC}"
echo -e "  ${DIM}DB push:      bun run db:push${NC}"
echo -e "  ${DIM}Ralph Zero:   cd skills/ralph-zero && source .venv/bin/activate${NC}"
echo ""
echo -e "${BOLD}  API Endpoints (24 routes):${NC}"
echo -e "  ${DIM}GET/POST  /api/click              — Process traffic${NC}"
echo -e "  ${DIM}GET/POST  /api/postback           — Conversion tracking${NC}"
echo -e "  ${DIM}GET       /api/admin/stats        — Dashboard statistics${NC}"
echo -e "  ${DIM}CRUD      /api/admin/campaigns    — Campaign management${NC}"
echo -e "  ${DIM}CRUD      /api/admin/streams      — Stream management${NC}"
echo -e "  ${DIM}CRUD      /api/admin/offers       — Offer management${NC}"
echo -e "  ${DIM}CRUD      /api/admin/landings     — Landing management${NC}"
echo -e "  ${DIM}CRUD      /api/admin/publishers   — Publisher management${NC}"
echo -e "  ${DIM}  ... and 16 more (see CLAUDE.md)${NC}"
echo ""
echo -e "${BOLD}  Installed Skills:${NC}"
if [ -d skills/ez-agents/.claude ]; then
  echo -e "  ${GREEN}✓${NC} EZ Agents v5.0.6  — 18 commands, 11 agents, 30+ domain skills"
else
  echo -e "  ${RED}✗${NC} EZ Agents — .claude/ not configured"
fi
if [ -d skills/ralph-zero/.venv ]; then
  echo -e "  ${GREEN}✓${NC} Ralph Zero v0.1.0  — Autonomous dev orchestrator"
else
  echo -e "  ${RED}✗${NC} Ralph Zero — .venv not created"
fi
echo ""
echo -e "${YELLOW}  Next session: download workspace .tar, extract, then:${NC}"
echo -e "        ${CYAN}bash bootstrap.sh${NC}"
echo ""
