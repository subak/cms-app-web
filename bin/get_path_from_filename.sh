#!/usr/bin/env bash

set -ex

filename=${1}
ls -1 content/${filename}.* | egrep '(md|rst|adoc)$' | head -n 1
