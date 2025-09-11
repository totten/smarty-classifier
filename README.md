# Smarty Classifier

Quick and dirty example of parsing Smarty TPL files and obtaining an AST.

## Usage

```bash
## Install dependencies
composer install

## Inspect the tags in a specific file
./bin/print-tags examples/input/greeter.tpl
./bin/print-tags examples/input/many.tpl

## Inspect the tags in all the example files
./bin/scan-examples

## Inspect the tags in civicrm
XDEBUG_MODE=off php -d memory_limit=2g ./bin/scan-examples /var/www/web/core/templates /tmp/scan-results
```

## TODO

* Grammar
    * More binary operators
    * Ternary operators
    * Method calls
