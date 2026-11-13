#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"

_SONAR_HOST_URL="${SONAR_HOST_URL:-}"
_SONAR_TOKEN="${SONAR_TOKEN:-}"
_SONAR_PROJECT_KEY="${SONAR_PROJECT_KEY:-}"

if [ -f "${SCRIPT_DIR}/.env" ]; then
  set -a
  . "${SCRIPT_DIR}/.env"
  set +a
elif [ -f .env ]; then
  set -a
  . .env
  set +a
fi

SONAR_HOST_URL="${_SONAR_HOST_URL:-${SONAR_HOST_URL:-http://172.17.0.2:9000}}"
SONAR_TOKEN="${_SONAR_TOKEN:-${SONAR_TOKEN:-}}"
SONAR_PROJECT_KEY="${_SONAR_PROJECT_KEY:-${SONAR_PROJECT_KEY:-Bludit}}"
OUTPUT_FILE="${OUTPUT_FILE:-issues.json}"

if [ -z "${SONAR_TOKEN}" ]; then
  echo "Error: SONAR_TOKEN is not set. Please set it in .env or as an environment variable." >&2
  exit 1
fi

BASE_URL="${SONAR_HOST_URL%/}"

curl -u "${SONAR_TOKEN}:" "${BASE_URL}/api/issues/search?componentKeys=${SONAR_PROJECT_KEY}&ps=500" -o "${OUTPUT_FILE}"
