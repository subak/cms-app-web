#!/usr/bin/env bash

set -ue

context="${@}"

uri=$(echo ${context} | jq -r '.uri')

input="html${uri%.css}.s[ac]ss"
out_dir=${BUILD:-/tmp/cms}
output="${out_dir}${uri}"

if [ -n "${BUILD:-}" ]; then

  sassc -t compressed ${input}

elif [ ! -e "${output}" ] || [ -n "$(find ${input} -newer ${output})" ]; then
  [ -e "${output%/*}" ] || mkdir -pv "${output%/*}" 1>/dev/stderr

  sassc -m -t compressed ${input} ${output}

  cat "${output}"

else

  cat "${output}"

fi

