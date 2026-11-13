#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"

# Save existing environment overrides if present
_SONAR_HOST_URL="${SONAR_HOST_URL:-}"
_SONAR_TOKEN="${SONAR_TOKEN:-}"

# Load .env if present
if [ -f "${SCRIPT_DIR}/.env" ]; then
  # shellcheck source=/dev/null
  set -a
  . "${SCRIPT_DIR}/.env"
  set +a
elif [ -f .env ]; then
  # shellcheck source=/dev/null
  set -a
  . .env
  set +a
fi

# Restore overrides or apply defaults
SONAR_HOST_URL="${_SONAR_HOST_URL:-${SONAR_HOST_URL:-http://172.17.0.2:9000}}"
SONAR_TOKEN="${_SONAR_TOKEN:-${SONAR_TOKEN:-}}"
REPO_DIR="${YOUR_REPO:-.}"

if [ -z "${SONAR_TOKEN}" ]; then
  echo "Error: SONAR_TOKEN is not set. Please set it in .env or as an environment variable." >&2
  exit 1
fi

docker run --rm \
  -e SONAR_HOST_URL="${SONAR_HOST_URL}" \
  -e SONAR_TOKEN="${SONAR_TOKEN}" \
  -v "${REPO_DIR}:/usr/src" \
  sonarsource/sonar-scanner-cli "$@"
