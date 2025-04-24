#!/bin/bash

cd $(dirname "$0")/..

VERSION=0.12

VERSION=$(grep --color=never -Po "^const VERSION = \"\K.*" ./wp-plugins/cf7-workshop-scheduler/index.php || true)
VERSION=${VERSION//\"/}
VERSION=${VERSION//;/}
echo $VERSION

mkdir -p release

rm -rf release/cf7-workshop-scheduler
cp -r wp-plugins/cf7-workshop-scheduler release/cf7-workshop-scheduler
cd release
zip -r cf7-workshop-scheduler-$VERSION.zip cf7-workshop-scheduler

echo "DONE!"
