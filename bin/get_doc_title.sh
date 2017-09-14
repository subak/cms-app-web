#!/usr/bin/env bash

set -eu

path=${1}

head -1 ${path} | sed -r 's/^[#= ]*(.+)[#= ]*+$/\1/'
