#!/usr/bin/env bash
# Restarts `php artisan serve` automatically if it dies.
#
# php artisan serve is a single-threaded, dev-only server — it can crash or
# hang while holding open a long streaming response (e.g. the AI Assistant's
# SSE chat stream). This is a workaround for local development only; it is
# not a substitute for a real app server in production.
set -u

cd "$(dirname "$0")"

while true; do
    php artisan serve --port="${PORT:-8000}"
    echo
    echo "[serve-dev] server exited (code $?) — restarting in 1s... (Ctrl+C to stop)"
    sleep 1
done
