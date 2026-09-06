#!/usr/bin/env bash
set -euo pipefail

rm -rf -- tmp/package tmp/assets tmp/tg-instantview.zip
mkdir -p -- tmp/package/tg-instantview tmp/assets

cp -R -- assets/. tmp/assets/
cp -R -- tg-instantview/. tmp/package/tg-instantview/

(
	cd tmp/package
	zip -qr ../tg-instantview.zip tg-instantview
)

echo "Created tmp/tg-instantview.zip"
