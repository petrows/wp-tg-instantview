#!/usr/bin/env bash
set -euo pipefail

SSH_TARGET=${SSH_TARGET:-viktorbush@77.222.61.69}
SSH_IDENTITY=${SSH_IDENTITY:-}
WP_ROOT=${WP_ROOT:-/home/v/viktorbush/2050su/domains/2050.su/public_html}
REF=${REF:-master}
REPOSITORY=${REPOSITORY:-viktor-drobek/wp-tg-instantview}
PHP_BIN=${PHP_BIN:-/usr/bin/php8.5}
SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)

printf -v REMOTE_COMMAND 'bash -s -- %q %q %q %q' \
	"${WP_ROOT}" \
	"${REF}" \
	"${REPOSITORY}" \
	"${PHP_BIN}"

SSH_ARGUMENTS=()
if [[ -n ${SSH_IDENTITY} ]]; then
	SSH_ARGUMENTS+=( -i "${SSH_IDENTITY}" )
fi

ssh "${SSH_ARGUMENTS[@]}" "${SSH_TARGET}" "${REMOTE_COMMAND}" < "${SCRIPT_DIR}/install-on-server.sh"
