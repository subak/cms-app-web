#!/usr/bin/env bash

find html/* -name *.php \( -type f -o -type l \) \
  | grep -v '/_' \
  | sed -e 's/^html//' \
  | sed -e 's/\.php$//'
