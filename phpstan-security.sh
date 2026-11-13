#!/usr/bin/env bash
set -e

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$PWD":/app \
    phpstan-security "$@"
