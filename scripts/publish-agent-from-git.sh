#!/usr/bin/env bash
# Run on the Laravel control-plane host after git pull (or from CI).
# Builds/publishes snmp-agent to the Laravel update channel.
#
# Usage:
#   VERSION=0.1.3 ./scripts/publish-agent-from-git.sh
#   VERSION=0.1.3 BINARY=/path/to/snmpd-linux-amd64 ./scripts/publish-agent-from-git.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="${VERSION:?set VERSION=x.y.z}"
NOTES="${NOTES:-}"
ARCH="${ARCH:-amd64}"
AGENT_DIR="${AGENT_DIR:-$ROOT/../snmp-agent}"
BINARY="${BINARY:-}"

cd "$ROOT"

if [[ -z "$BINARY" ]]; then
  if [[ ! -d "$AGENT_DIR" ]]; then
    echo "snmp-agent dir not found at $AGENT_DIR (set AGENT_DIR= or BINARY=)" >&2
    exit 1
  fi
  export PATH="${HOME}/sdk/go/bin:${PATH}"
  (
    cd "$AGENT_DIR"
    make release VERSION="$VERSION"
  )
  BINARY="$AGENT_DIR/dist/snmpd-linux-${ARCH}"
fi

test -f "$BINARY" || { echo "missing binary: $BINARY" >&2; exit 1; }

php artisan agent:publish-release "$VERSION" "$BINARY" \
  --arch="$ARCH" \
  --notes="$NOTES" \
  --push-channel

echo
echo "Done. In Laravel UI: Agent Updates → Check for updates → Make update"
