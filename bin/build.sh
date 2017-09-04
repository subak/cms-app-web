#!/usr/bin/env bash

export BUILD=$2

set -eu

context=$1
out_dir=$2

out_dir_context='+ {"out_dir": "'"${out_dir}"'"}'
context=$(echo ${context} | jq -c ". ${out_dir_context}")

uri=$(echo ${context} | jq -r .uri)
handler=$(echo ${context} | jq -r .handler)

to_file=${out_dir}${uri}
to_dir=$(echo ${to_file} | sed -e 's/\/[^/]*$//')
echo ${to_file} | egrep '/$' >/dev/null && to_file=${to_dir}/index.html

[ -e ${to_dir} ] || mkdir -pv ${to_dir}

res=$(eval "${handler} '${context}'")
[ "${?}" -eq 0 ] && echo "${res}" > ${to_file} && echo ${to_file}
exit 0
