#!/usr/bin/env bash

set -eu

length=${1}
avoid_confusion=${2:-true}
excepted_letter="+/="
[[ "${avoid_confusion}" == 'true' ]] && excepted_letter="${excepted_letter}Il10Oo"
id=$(openssl rand -base64 60 | sed -e "s@[${excepted_letter}]@@g")
echo ${id:0:${length}}