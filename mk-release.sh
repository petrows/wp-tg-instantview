#!/bin/bash

rm -rf tmp/trunk
mkdir -p tmp/trunk tmp/assets

cp -rva assets/* tmp/assets/ ; cp -rva tg-instantview/* tmp/trunk/

cd tmp/trunk
zip ../tg-instantview.zip *
