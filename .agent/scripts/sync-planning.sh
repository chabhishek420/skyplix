#!/usr/bin/env bash
# sync-planning.sh — Keep .planning/codebase/ in sync with .gsd/
#
# Direction: .gsd (authoritative) → .planning/codebase/ (read by opencode)
#
# Run manually:       .agent/scripts/sync-planning.sh
# Run automatically:  called by /pause and /execute workflows (see .agent/workflows/)
#
# Mapping:
#   .gsd/ARCHITECTURE.md → .planning/codebase/ARCHITECTURE.md
#   .gsd/STACK.md         → .planning/codebase/STACK.md
#   .gsd/STATE.md         → .planning/codebase/CONCERNS.md   (active progress/blockers)
#   .gsd/SPEC.md          → .planning/codebase/CONVENTIONS.md (what we're building)

set -e

GSD="/Users/roshansharma/Desktop/zai-yt-keitaro/.gsd"
PLANNING="/Users/roshansharma/Desktop/zai-yt-keitaro/.planning/codebase"

# Ensure target directory exists
mkdir -p "$PLANNING"

sync_file() {
  local src="$1"
  local dst="$2"
  if [ -f "$src" ]; then
    cp "$src" "$dst"
    echo "  ✓ $(basename "$src") → $(basename "$dst")"
  else
    echo "  ⚠ Source missing: $src"
  fi
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo " GSD → .planning sync"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

sync_file "$GSD/ARCHITECTURE.md" "$PLANNING/ARCHITECTURE.md"
sync_file "$GSD/STACK.md"        "$PLANNING/STACK.md"
sync_file "$GSD/STATE.md"        "$PLANNING/CONCERNS.md"
sync_file "$GSD/SPEC.md"         "$PLANNING/CONVENTIONS.md"
sync_file "$GSD/ROADMAP.md"      "$PLANNING/ROADMAP.md"     2>/dev/null || true

echo ""
echo "Synced at: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Source:    $GSD"
echo "Target:    $PLANNING"
