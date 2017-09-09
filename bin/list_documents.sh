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
  
  time=$(date --iso-8601=minutes -r ${path})
  created=$(cd ${dir} && git log --date=iso --pretty=format:"%cd" ${name} | tail -1 | sed -e 's/ /T/' | sed -e 's/ //')
  updated=$(cd ${dir} && git log --date=iso --pretty=format:"%cd" -n 1 ${name} | sed -e 's/ /T/' | sed -e 's/ //')
  [[ -z "${created}" ]] && created=${time}
  [[ -z "${updated}" ]] && updated=${time}
  
  [[ -e ${meta_path} ]] && meta=$(yaml2json ${meta_path}) || meta='{}'
  [[ -e ${doc_meta_path} ]] && doc_meta=$(yaml2json ${doc_meta_path}) || doc_meta='{}'
  
  cat <<EOF | cat - <(echo "${meta} ${doc_meta}") | jq -s add
{
  "path": "${path}",
  "created": "${created}",
  "updated": "${updated}"  
}
EOF
  
  glue=','
done
echo ']'
