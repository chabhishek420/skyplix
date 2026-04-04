#!/usr/bin/env bash
set -e

echo "Syncing CLAUDE.md with current .gsd state..."
STATE_FILE=".gsd/STATE.md"
CLAUDE_FILE="CLAUDE.md"

if [ ! -f "$STATE_FILE" ]; then
    echo "Error: $STATE_FILE not found."
    exit 1
fi

if [ ! -f "$CLAUDE_FILE" ]; then
    echo "Creating new CLAUDE.md..."
    cat << 'EOF' > "$CLAUDE_FILE"
# SkyPlix Project Context
EOF
fi

# Remove previous sync block and re-add latest phase overview
sed -i.bak '/## Latest GSD Status Sync/,$d' "$CLAUDE_FILE"
rm -f "${CLAUDE_FILE}.bak"

echo -e "\n## Latest GSD Status Sync" >> "$CLAUDE_FILE"
echo "Auto-generated from $STATE_FILE on $(date)" >> "$CLAUDE_FILE"
echo "" >> "$CLAUDE_FILE"

# Extract everything from "## Current Phase Overview" to the next "## " if possible, or just tail it.
# Simple extraction:
awk '/## Current Phase Overview/{flag=1; print; next} /## /{if(flag) {flag=0}} flag' "$STATE_FILE" >> "$CLAUDE_FILE"

echo "CLAUDE.md successfully synchronized with latest GSD Phase status."
