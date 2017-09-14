#!/usr/bin/env bash

# usage: cache.sh content/posts list_documents.sh content/posts index

set -eu

hash=$(echo "${@}" | shasum | cut -f1 -d' ')
path=/tmp/${hash}
basis=${1}




if [[ -e ${path} ]] && [[ $(stat -c %Y ${path}) -gt $(stat -c %Y ${basis}) ]]; then
  cat ${path}
else
  ${2} ${@:3} > ${path}
  cat ${path}
fi