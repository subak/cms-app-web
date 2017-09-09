#!/usr/bin/env bash

set -eu

target=${1}
glue=''
index_name=${2}

echo '['
for path in $(find content/entry -maxdepth 2 -regextype posix-egrep -regex '^.*\.(md|rst|adoc)$'); do
  echo ${glue}

  dir=$(dirname ${path})
  name=$(basename ${path})
  meta_path=${dir}/meta.yml
  doc_meta_path=${dir}/${index_name}.yml
  
  [[ -e ${meta_path} ]] && meta=$(yaml2json ${meta_path}) || meta='{}'
  [[ -e ${doc_meta_path} ]] && doc_meta=$(yaml2json ${doc_meta_path}) || doc_meta='{}'
  
  cat <<EOF | cat - <(echo "${meta} ${doc_meta}") | jq -s add
{
  "path": "${path}",
  "created": "$(created.sh ${path})",
  "updated": "$(updated.sh ${path})"
}
EOF
  
  glue=','
done
echo ']'
