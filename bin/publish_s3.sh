#!/usr/bin/env bash

publish.sh /tmp/s3 global
aws s3 sync /tmp/s3 ${S3_BUCKET} --delete
