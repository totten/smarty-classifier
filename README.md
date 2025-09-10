# Smarty Classifier

Quick and dirty example of parsing Smarty TPL files and obtaining an AST.

## Example

```bash
composer install

./bin/smarty-classifier examples/greeter.tpl

./bin/smarty-classifier examples/many.tpl

./bin/smarty-classifier examples/big.tpl
```

## TODO

* Grammar
    * More binary operators
    * Method calls
