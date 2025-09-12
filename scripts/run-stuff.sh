#!/usr/bin/env bash

## Run some random example commands. Do they crash?
set -e

function print_h1() {
  echo
  echo ==============================================
  echo == "$@"
  echo ==============================================
}

EXAMPLE_FILE=examples/input/greeter.tpl

print_h1 print-advice
./bin/smartyup print-advice "$EXAMPLE_FILE"

print_h1 print-stanzas
./bin/smartyup print-stanzas "$EXAMPLE_FILE"

print_h1 print-tags
./bin/smartyup print-tags "$EXAMPLE_FILE"

print_h1 parse
echo '{$name|smarty:nodefaults}' | ./bin/smartyup parse

print_h1 scan
./bin/smartyup scan
