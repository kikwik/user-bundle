#!/usr/bin/env bash

set -u

PHPUNIT_ARGS=("$@")
FAILED=0
FAILED_TESTS_SUMMARY=()

run_matrix() {
    local php_bin="$1"
    local symfony_require="$2"
    local composer_flags="$3"

    echo
    echo "============================================================"
    echo "PHP:     $("$php_bin" -r 'echo PHP_VERSION;')"
    echo "Symfony: ${symfony_require}"

    local composer_log_file
    composer_log_file="$(mktemp)"

    if ! SYMFONY_REQUIRE="$symfony_require" "$php_bin" composer.phar update \
        --with-all-dependencies \
        --no-interaction \
        --no-progress \
        --no-blocking \
        $composer_flags \
        >"$composer_log_file" 2>&1
    then
        echo "Composer update fallito per PHP ${php_bin} / Symfony ${symfony_require}"
        echo
        cat "$composer_log_file"
        rm -f "$composer_log_file"

        FAILED=1
        FAILED_TESTS_SUMMARY+=("PHP ${php_bin} / Symfony ${symfony_require}: composer update fallito")
        return
    fi

    rm -f "$composer_log_file"

    echo
    echo "Risultato test:"

    local phpunit_log_file
    phpunit_log_file="$(mktemp)"

    "$php_bin" vendor/bin/phpunit --testdox "${PHPUNIT_ARGS[@]}" 2>&1 | tee "$phpunit_log_file"
    local test_exit_code="${PIPESTATUS[0]}"

    if [ "$test_exit_code" -ne 0 ]; then
        FAILED=1

        local failed_details
        failed_details="$(
            grep -E '^[[:space:]]*[✘✖✗]|^[[:space:]]*[0-9]+\)[[:space:]]' "$phpunit_log_file" || true
        )"

        if [ -n "$failed_details" ]; then
            FAILED_TESTS_SUMMARY+=("PHP ${php_bin} / Symfony ${symfony_require}: test falliti"$'\n'"${failed_details}")
        else
            FAILED_TESTS_SUMMARY+=("PHP ${php_bin} / Symfony ${symfony_require}: test passati, con deprecations")
        fi
    else
       FAILED_TESTS_SUMMARY+=("PHP ${php_bin} / Symfony ${symfony_require}: test passati")
    fi

    rm -f "$phpunit_log_file"
    echo "============================================================"
    echo
}

symfony composer global require symfony/flex \
    --no-interaction \
    --no-progress \
    --quiet

run_matrix "php8.2" "6.4.*" "--prefer-lowest"
run_matrix "php8.2" "7.0.*" ""
run_matrix "php8.2" "7.1.*" ""
run_matrix "php8.2" "7.2.*" ""
run_matrix "php8.2" "7.3.*" ""
run_matrix "php8.2" "7.4.*" ""
run_matrix "php8.3" "7.4.*" ""
run_matrix "php8.4" "7.4.*" ""
run_matrix "php8.5" "7.4.*" ""

echo
echo "============================================================"
echo "Riepilogo finale"
echo "============================================================"

if [ "${#FAILED_TESTS_SUMMARY[@]}" -eq 0 ]; then
    echo "Tutti i test sono passati."
else
    echo "Sono stati rilevati fallimenti:"
    echo

    for summary in "${FAILED_TESTS_SUMMARY[@]}"; do
        echo "$summary"
        echo
    done
fi

exit "$FAILED"