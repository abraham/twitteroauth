#!/bin/bash

CURRENT_COMMAND="$(curl -s "https://curl.haxx.se/ca/cacert.pem.sha256")"
CURRENT_PARTS=($CURRENT_COMMAND)
CURRENT_SHA="${CURRENT_PARTS[0]}"

FILE_COMMAND="$(openssl sha -sha256 src/cacert.pem)"
FILE_PARTS=($FILE_COMMAND)
FILE_SHA="${FILE_PARTS[1]}"

if [ "$FILE_SHA" = "$CURRENT_SHA" ]; then
  echo "cacert.pem is current"
  exit 0
fi

echo "cacert.pem needs to be updated."
exit 1
