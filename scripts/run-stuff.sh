#!/usr/bin/env bash

## Run some random example commands. Do they crash?
set -e

function print_h1() {
  echo
  echo ==============================================
  echo == "$@"
  echo ==============================================
}

print_h1 print-advice
./bin/smartyup print-advice examples/temp.tpl

print_h1 print-stanzas
./bin/smartyup print-stanzas examples/temp.tpl

print_h1 print-tags
./bin/smartyup print-tags examples/temp.tpl

print_h1 parse-tag
echo '{$name|smarty:nodefaults}' | ./bin/smartyup parse-tag

print_h1 scan-examples
./bin/smartyup scan-examples
