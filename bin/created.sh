#!/usr/bin/env bash

path=${1}
dir=$(dirname ${path})
name=$(basename ${path})

created=$(cd ${dir} && git log --follow --date=iso --pretty=format:"%cd" ${name} | tail -1 | sed -e 's/ /T/' | sed -e 's/ //')
[[ -z "${created}" ]] && created=$(date --iso-8601=minutes -r ${path})

echo ${created}