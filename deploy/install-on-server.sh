#!/usr/bin/env bash
set -euo pipefail

WP_ROOT=${1:?WordPress root is required}
REF=${2:-master}
REPOSITORY=${3:-viktor-drobek/wp-tg-instantview}
PHP_BIN=${4:-/usr/bin/php8.5}

PLUGIN_SLUG=tg-instantview
PLUGIN_FILE=${PLUGIN_SLUG}/tg-instantview.php
PLUGINS_DIR=${WP_ROOT}/wp-content/plugins
TARGET_DIR=${PLUGINS_DIR}/${PLUGIN_SLUG}
UPGRADE_DIR=${WP_ROOT}/wp-content/upgrade
TIMESTAMP=$(date -u +%Y%m%dT%H%M%SZ)
BACKUP_DIR=${UPGRADE_DIR}/${PLUGIN_SLUG}-backup-${TIMESTAMP}
STAGING_DIR=${PLUGINS_DIR}/.${PLUGIN_SLUG}-new-${TIMESTAMP}
TEMP_DIR=$(mktemp -d "${TMPDIR:-/tmp}/tgiv-deploy.XXXXXXXX")

cleanup() {
	if [[ -d ${STAGING_DIR} ]]; then
		rm -rf -- "${STAGING_DIR}"
	fi
	rm -rf -- "${TEMP_DIR}"
}
trap cleanup EXIT

if [[ ! -f ${WP_ROOT}/wp-load.php ]]; then
	echo "WordPress not found at ${WP_ROOT}" >&2
	exit 1
fi

if [[ ! -x ${PHP_BIN} ]]; then
	echo "PHP executable not found: ${PHP_BIN}" >&2
	exit 1
fi

mkdir -p -- "${PLUGINS_DIR}" "${UPGRADE_DIR}" "${STAGING_DIR}"

ARCHIVE_URL="https://codeload.github.com/${REPOSITORY}/tar.gz/${REF}"
curl --fail --location --silent --show-error \
	--output "${TEMP_DIR}/source.tar.gz" \
	"${ARCHIVE_URL}"

tar -xzf "${TEMP_DIR}/source.tar.gz" -C "${TEMP_DIR}"
SOURCE_FILE=$(find "${TEMP_DIR}" -path "*/${PLUGIN_FILE}" -type f -print -quit)

if [[ -z ${SOURCE_FILE} ]]; then
	echo "Plugin source not found in ${ARCHIVE_URL}" >&2
	exit 1
fi

SOURCE_DIR=$(dirname "${SOURCE_FILE}")
cp -R -- "${SOURCE_DIR}/." "${STAGING_DIR}/"

while IFS= read -r php_file; do
	"${PHP_BIN}" -l "${php_file}" >/dev/null
done < <(find "${STAGING_DIR}" -type f -name '*.php' -print)

if [[ -d ${TARGET_DIR} ]]; then
	mv -- "${TARGET_DIR}" "${BACKUP_DIR}"
fi
if ! mv -- "${STAGING_DIR}" "${TARGET_DIR}"; then
	if [[ -d ${BACKUP_DIR} && ! -d ${TARGET_DIR} ]]; then
		mv -- "${BACKUP_DIR}" "${TARGET_DIR}"
	fi
	echo "Could not install staged plugin; previous plugin restored." >&2
	exit 1
fi

if ! "${PHP_BIN}" -d display_errors=1 -r '
	$wp_root = $argv[1];
	chdir( $wp_root );
	require $wp_root . "/wp-load.php";
	require_once ABSPATH . "wp-admin/includes/plugin.php";
	$plugin = "tg-instantview/tg-instantview.php";
	if ( ! is_plugin_active( $plugin ) ) {
		$result = activate_plugin( $plugin );
		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, $result->get_error_message() . PHP_EOL );
			exit( 1 );
		}
	}
	if ( function_exists( "w3tc_flush_all" ) ) {
		w3tc_flush_all();
	}
	wp_cache_flush();
	echo "plugin_active=" . ( is_plugin_active( $plugin ) ? "YES" : "NO" ) . PHP_EOL;
	echo "plugin_version=" . get_plugin_data( WP_PLUGIN_DIR . "/" . $plugin, false, false )["Version"] . PHP_EOL;
' "${WP_ROOT}"; then
	FAILED_DIR=${UPGRADE_DIR}/${PLUGIN_SLUG}-failed-${TIMESTAMP}
	mv -- "${TARGET_DIR}" "${FAILED_DIR}"
	if [[ -d ${BACKUP_DIR} ]]; then
		mv -- "${BACKUP_DIR}" "${TARGET_DIR}"
	fi
	echo "Deployment failed; previous plugin restored. Failed files: ${FAILED_DIR}" >&2
	exit 1
fi

echo "Deployment complete: ${TARGET_DIR}"
if [[ -d ${BACKUP_DIR} ]]; then
	echo "Backup: ${BACKUP_DIR}"
fi
