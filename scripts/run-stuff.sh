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

print_h1 debug:advisor
./bin/smartyup debug:advisor "$EXAMPLE_FILE"

print_h1 debug:stanzas
./bin/smartyup debug:stanzas "$EXAMPLE_FILE"

print_h1 debug:tags
./bin/smartyup debug:tags "$EXAMPLE_FILE"

print_h1 parse
echo '{$name|smarty:nodefaults}' | ./bin/smartyup parse

print_h1 debug:dump
./bin/smartyup debug:dump
