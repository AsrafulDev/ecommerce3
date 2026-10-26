#!/usr/bin/env bash
# Rehearse a release on an isolated STAGING database. Never touches the live database.
#
# Usage:
#   STAGING_DB=ecommerce3_staging ./scripts/staging-rehearsal.sh            # migrate only
#   STAGING_DB=ecommerce3_staging SANITISED_DUMP=/path/sanitised.sql ./scripts/staging-rehearsal.sh
#   DRY_RUN=1 STAGING_DB=ecommerce3_staging ./scripts/staging-rehearsal.sh  # print steps, change nothing
#
# Refuses to run if STAGING_DB is the live database, or does not look like a staging name.
# A dump must be sanitised first (personal data removed); this script does not sanitise.
set -euo pipefail

LIVE_DB="${LIVE_DB:-ecommerce3}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:-root}"
DRY_RUN="${DRY_RUN:-0}"

: "${STAGING_DB:?Set STAGING_DB to the staging database name}"

if [[ "$STAGING_DB" == "$LIVE_DB" ]]; then
    echo "REFUSED: STAGING_DB equals the live database ($LIVE_DB)." >&2
    exit 1
fi
if [[ "$STAGING_DB" != *staging* && "$STAGING_DB" != *rehearsal* ]]; then
    echo "REFUSED: STAGING_DB must contain 'staging' or 'rehearsal' (got $STAGING_DB)." >&2
    exit 1
fi
if [[ "$STAGING_DB" == *prod* || "$STAGING_DB" == *live* ]]; then
    echo "REFUSED: STAGING_DB name suggests production or live data." >&2
    exit 1
fi

run() {
    if [[ "$DRY_RUN" == "1" ]]; then
        echo "[dry-run] $*"
    else
        "$@"
    fi
}

# Integrations are switched off for the rehearsal: no real mail, no Facebook events, sync queue.
export DB_DATABASE="$STAGING_DB"
export MAIL_MAILER=log
export FACEBOOK_ACCESS_TOKEN=
export FACEBOOK_PIXEL_ID=
export QUEUE_CONNECTION=sync
export DEMO_MODE=true

echo "Rehearsal database: $STAGING_DB (live database $LIVE_DB is not touched)"

run mysql -h"$DB_HOST" -u"$DB_USER" -e "CREATE DATABASE IF NOT EXISTS \`$STAGING_DB\`"

if [[ -n "${SANITISED_DUMP:-}" ]]; then
    if [[ ! -f "$SANITISED_DUMP" ]]; then
        echo "REFUSED: SANITISED_DUMP does not exist: $SANITISED_DUMP" >&2
        exit 1
    fi
    echo "Importing sanitised dump into $STAGING_DB"
    run sh -c "mysql -h'$DB_HOST' -u'$DB_USER' '$STAGING_DB' < '$SANITISED_DUMP'"
fi

echo "Migration status before:"
run php artisan migrate:status
echo "Applying pending migrations to $STAGING_DB"
run php artisan migrate --force
echo "Migration status after:"
run php artisan migrate:status

echo "Done. The full test suite is NOT run against this database: its tests refresh the database they use."
echo "Run the suite against ecommerce3_test (the .env.testing database) instead."
