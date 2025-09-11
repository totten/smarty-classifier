# Smarty Classifier

Quick and dirty example of parsing Smarty TPL files and obtaining an AST.

## Usage

```bash
## Install dependencies
composer install

## Print the advice for a specific file
./bin/print-advice examples/input/many.tpl

## Inspect the tags in a specific file
./bin/print-tags examples/input/greeter.tpl
./bin/print-tags examples/input/many.tpl

## Inspect the tags in all the example files
./bin/scan-examples

## Inspect the tags in civicrm
XDEBUG_MODE=off php -d memory_limit=2g ./bin/scan-examples /var/www/web/core/templates /tmp/scan-results
```

## Advice

At the moment, the `print-advice` script will analyze file and report on the
Smarty "tags" (`{...}` blocks).  It will identify blocks which appear safe,
problematic, or unsure.

For example, at time of writing it generates this report for `Mapper.tpl`:

```
# /Users/me/bknix/build/dev/web/core/templates/CRM/Contact/Import/Form/Mapper.tpl

## OK:
- TAG: `{ts}`
- TAG: `{assign var='i' value=$smarty.section.count.index}`

## PROBLEM: Use "nofilter" or "|escape nofilter" for portability:
- TAG: `{$form.mapper[$i].label}`
- TAG: `{$i}`
- TAG: `{$form.buttons.html}`

## PROBLEM: Change "smarty:nodefaults" to "nofilter" for portability:
- TAG: `{$form.mapper[$i].html|smarty:nodefaults}`
```

Note: The output is likely to change. The README may be updated occasionally
to reflect new output, but it will not be kept strictly up-to-date.

## TODO

* `print-advice`
    * Run against a larger data-set. Inspect advice. Fine-tune rules.
    * Generate and apply suggested revisions.
* Grammar
    * More binary operators
