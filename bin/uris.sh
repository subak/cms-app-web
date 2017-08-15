#!/usr/bin/env bash

for indexer in $(find */bin/indexers/*); do
  echo "$(${indexer})"
done