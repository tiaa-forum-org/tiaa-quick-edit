#!/usr/bin/env bash

cd ../../
if [ -d "../../../../tiaa-dev/tiaa-backup/" ]; then
  TIAA_BACKUP="../../../../tiaa-dev/tiaa-backup"
else
  echo "no target directory"
  exit 1
fi

current_date=$(date "+%y%m%d%H%M")

zip -r /tmp/tiaa-quick-edit.zip tiaa-quick-edit -x "*/bin/*" "*/.git/*"

cp /tmp/tiaa-quick-edit.zip "${TIAA_BACKUP}/${current_date}tiaa-quick-edit.zip"

cd ${TIAA_BACKUP} || exit
TIAA_BACKUP_DIR=$(pwd)
echo "saved in ${TIAA_BACKUP_DIR}/${current_date}tiaa-quick-edit.zip"