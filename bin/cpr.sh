#!/usr/bin/env bash

set -eu

src_dir=${1}
dst_dir=${2}
strip=${3:-}
filter=${4:-}

cmd='find "${src_dir}" -type f'

[[ -n "${filter}" ]] && cmd="${cmd} | egrep -E '${filter}'"

for path in $(eval "${cmd}"); do
  dst="${dst_dir}${path#${strip}}"

  mkdir -pv $(dirname "${dst}")
  cp -v "${path}" "${dst}"
done
