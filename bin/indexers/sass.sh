#!/usr/bin/env bash

find html/* -name '*.s[ac]ss' \( -type f -o -type l \) \
  | grep -v '/_' \
  | sed 's/^html//' \
  | sed -E 's/\.s[ac]ss$/.css/'
