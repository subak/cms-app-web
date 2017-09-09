#!/usr/bin/env bash

key=${1}

context=''
for app in ${APP_STACK}; do
  context="${context} $(yaml2json ${app}/config/meta.yml)"
done

echo ${context} | jq -s add | jq -r ".${key}" 
