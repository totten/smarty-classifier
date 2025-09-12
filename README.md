# Smarty-Up

Scan Smarty templates and suggest updates.

Note: This includes a parser to help analyze/transform Smarty code.  It is based
on a quick-and-dirty interpretation of the Smarty grammar.  It may not be a
perfect match to the real+current Smarty interpreter, but it's being
developed against a large dataset, and it should be more accurate than
improvised regex.

## Usage

```bash
## Install dependencies
composer install

## Print the advice for a specific file
./bin/smartyup print-advice examples/input/many.tpl

## Print the parse-tree for a block of text
echo 'Hello {$name}' | ./bin/smartyup parse

## Print the parse-tree for file
./bin/smartyup parse < examples/input/greeter.tpl

## Inspect the tags in all the example files
./bin/smartyup scan-examples

## Inspect the tags in civicrm
XDEBUG_MODE=off php -d memory_limit=2g \
  ./bin/smartyup scan-examples -i /var/www/web/core/templates -o /tmp/scan-results
```

## Advice

At the moment, the `print-advice` script will analyze file and report on the
Smarty "tags" (`{...}` blocks).  It will identify blocks which appear safe,
problematic, or unsure.

For example, at time of writing it generates this report for `Preview.tpl`:

```
# /Users/totten/bknix/build/dev/web/core/templates/CRM/Contact/Import/Form/Preview.tpl

## OK
- TAG: `{include file="CRM/common/WizardHeader.tpl"}`
- TAG: `{ts}`
- TAG: `{if $invalidRowCount}`
- TAG: `{include file="CRM/Contact/Import/Form/MapTable.tpl"}`
- TAG: `{if !empty($form.groups)}`
- TAG: `{include file="CRM/common/formButtons.tpl" location="bottom"}`

## WARNING: Block has printable, dynamic parameters
- TAG: `{ts 1=$invalidRowCount 2=$downloadErrorRecordsUrl|smarty:nodefaults}`

## PROBLEM: It is unclear if the variable has HTML-markup or plain-text. Choose unambiguous notation:
- TAG: `{$totalRowCount}`
  SUGGEST #1: `{$totalRowCount nofilter}`
  SUGGEST #2: `{$totalRowCount|escape nofilter}`
- TAG: `{$invalidRowCount}`
  SUGGEST #1: `{$invalidRowCount nofilter}`
  SUGGEST #2: `{$invalidRowCount|escape nofilter}`
- TAG: `{$validRowCount}`
  SUGGEST #1: `{$validRowCount nofilter}`
  SUGGEST #2: `{$validRowCount|escape nofilter}`

## PROBLEM: In Smarty v5, "smarty:nodefaults" does not work. Use "nofilter".
- TAG: `{$downloadErrorRecordsUrl|smarty:nodefaults}`
  SUGGEST: {$downloadErrorRecordsUrl nofilter}

## PROBLEM: This looks like an HTML widget. Specify "nofilter".
- TAG: `{$form.newGroupName.label}`
  SUGGEST: {$form.newGroupName.label nofilter}
- TAG: `{$form.newGroupName.html}`
  SUGGEST: {$form.newGroupName.html nofilter}
- TAG: `{$form.newGroupDesc.label}`
  SUGGEST: {$form.newGroupDesc.label nofilter}
- TAG: `{$form.newGroupDesc.html}`
  SUGGEST: {$form.newGroupDesc.html nofilter}
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
