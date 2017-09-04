#!/usr/bin/env bash

set -eu

jq ". + $(echo [\"${APP_STACK// /\",\"}\"] | jq 'reverse | {"app_stack":.}')" < /dev/stdin

