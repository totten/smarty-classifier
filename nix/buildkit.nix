{ pkgs ? import <nixpkgs> {} }:

## Get civicrm-buildkit from github.
## Based on "master" branch circa 2025-09-12 03:24 UTC
import (pkgs.fetchzip {
  url = "https://github.com/civicrm/civicrm-buildkit/archive/edd7db7ab398abba5f62a96a35bb700ac03a66f1.tar.gz";
  sha256 = "0llwk63nwwpiahrgcqcbpqbldw7bqxq98wc3vkxqlyk1wlqjlf3y";
})

## Get a local copy of civicrm-buildkit. (Useful for developing patches.)
# import ((builtins.getEnv "HOME") + "/buildkit/default.nix")
# import ((builtins.getEnv "HOME") + "/bknix/default.nix")
