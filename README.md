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

For example, at time of writing it generates this report for `Preview.tpl`:

```
# /Users/totten/bknix/build/dev/web/core/templates/CRM/Contact/Import/Form/Preview.tpl

## OK:
- TAG: `{include file="CRM/common/WizardHeader.tpl"}`
- TAG: `{ts}`
- TAG: `{if $invalidRowCount}`
- TAG: `{include file="CRM/Contact/Import/Form/MapTable.tpl"}`
- TAG: `{if !empty($form.groups)}`
- TAG: `{include file="CRM/common/formButtons.tpl" location="bottom"}`

## WARNING: Block has printable, dynamic parameters
- TAG: `{ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl|smarty:nodefaults}`

## PROBLEM: Use "nofilter" or "|escape nofilter" for portability:
- TAG: `{$totalRowCount}`
- TAG: `{$invalidRowCount}`
- TAG: `{$validRowCount}`
- TAG: `{$form.newGroupName.label}`
- TAG: `{$form.newGroupName.html}`
- TAG: `{$form.newGroupDesc.label}`
- TAG: `{$form.newGroupDesc.html}`
- TAG: `{$form.newGroupType.label}`
- TAG: `{$form.newGroupType.html}`
- TAG: `{$form.groups.label}`
- TAG: `{$form.groups.html}`
- TAG: `{$form.newTagName.label}`
- TAG: `{$form.newTagName.html}`
- TAG: `{$form.newTagDesc.label}`
- TAG: `{$form.newTagDesc.html}`
- TAG: `{$form.tag.html}`

## PROBLEM: Change "smarty:nodefaults" to "nofilter" for portability:
- TAG: `{$downloadErrorRecordsUrl|smarty:nodefaults}`
```

Note: The output is likely to change. The README may be updated occasionally
to reflect new output, but it will not be kept strictly up-to-date.

## TODO

(*No particular order*)

* Fix PHP 8.2 warning in upstream project (`farafiri/php-parsing-tool`)
* `print-advice`
    * Run against a larger data-set. Inspect advice. Fine-tune rules.
    * Generate and apply suggested revisions.
* Grammar
    * More binary operators
