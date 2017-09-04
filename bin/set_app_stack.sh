#!/usr/bin/env bash

export APP_STACK="${@}"

for dir in "${@}"; do
  PATH="${PWD}/$(echo ${dir//"'"/""})/bin:${PATH}"
done

