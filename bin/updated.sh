#!/usr/bin/env bash

path=${1}
dir=$(dirname ${path})
name=$(basename ${path})

updated=$(cd ${dir} && git log --date=iso --pretty=format:"%cd" -n 1 ${name} | sed -e 's/ /T/' | sed -e 's/ //')
[[ -z "${updated}" ]] && updated=$(date --iso-8601=minutes -r ${path})

echo ${updated}