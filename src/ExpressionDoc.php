<?php

namespace Civi\SmartyUp;

use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;

/**
 * Represent an expression (`{$foo.bar.biz|modifier_1|modifier_2}`). Provide helper
 * methods for inspecting it and for creating derivative expressions.
 *
 * NOTE: By convention, the `withXXX()` methods will generate a new document with modifications applied.
 */
class ExpressionDoc {

  public Root $root;

  /**
   * @param string|\ParserGenerator\SyntaxTreeNode\Root $root
   */
  public function __construct($root) {
    $this->root = is_string($root) ? Services::createTagParser()->parse($root) : $root;
    $this->assertRootType('tag', 'expression');
  }

  public function __toString(): string {
    return $this->root->__toString();
  }

  /**
   * Append the "nofilter" flag to the {$expression}.
   *
   * @return static
   *   Ex: `{$foo.bar|whiz:bang nofilter}`
   */
  public function withNofilter(): ExpressionDoc {
    $mutableRoot = $this->root->copy();
    $tag = $mutableRoot->findFirst('tag:expression');
    $tag->setSubnode(2, new Branch('&choices', NULL, [
      new Branch('sp', NULL, [new Leaf(' ')]),
      new Branch('nofilter', NULL, [new Leaf('nofilter')]),
    ]));
    return new static($mutableRoot);
  }

  /**
   * Append another modifier to the {$expression}.
   *
   * @param string $text
   *   Ex: '|escape:url'
   * @return static
   */
  public function withModifier(string $text): ExpressionDoc {
    $mutableRoot = $this->root->copy();
    $modifierList = $mutableRoot->findFirst('list:modifier');
    $modifierList->setSubnodes([
      ...$modifierList->getSubnodes(),
      new Branch('modifier', 0, [new Leaf($text)]),
    ]);
    return new static($mutableRoot);
  }

  /**
   * Get a list of the modifiers in a more intuitive array.
   *
   * @param string|null $name
   *   Optionally filter by the name of the modifier.
   *   Ex: "escape" or "smarty".
   * @return array
   *   List of modifiers. Each modifier is an array with its name and parameters.
   *   Ex: [ ['smarty', 'nodefaults'], ['escape', 'html'] ]
   *   Ex: [ ['@json_encode'] ]
   */
  public function findModifiers(?string $name = NULL): array {
    $modifierList = $this->root->findAll('modifier');
    if (!$modifierList) {
      return [];
    }
    $result = [];
    foreach ($modifierList as $modifier) {
      $modifierName = (string) $modifier->findFirst('modifier_name');
      if ($name === NULL || $name === $modifierName) {
        $result[] = $this->convertModifierToArray($modifier);
      }
    }
    return $result;
  }

  /**
   * Walk through the list of all modifiers (in order). Optionally modify each one.
   *
   * @param callable $filter
   *   Filter function: function(string $name, ...$args): string|null|array
   *   Ex: $filter('smarty', 'nodefaults') => 'delete'
   *   Ex: $filter('substr', '1', '-1') => ['trim']
   *   Return one of the following:
   *     - NULL: Preserve the modifier as-is
   *     - 'keep': Preserve the modifier as-is
   *     - 'delete': Delete the modifier from the list
   *     - array(): Replace the content of the modifier
   *
   * @return static
   */
  public function filterModifiers(callable $filter): ExpressionDoc {
    $mutableRoot = $this->root->copy();
    $modifierList = $mutableRoot->findAll('modifier');
    if (!$modifierList) {
      return new static($mutableRoot);
    }

    $modified = FALSE;
    foreach ($modifierList as $modifier) {
      $startArray = $this->convertModifierToArray($modifier);
      $endArray = $filter(...$startArray);
      if ($endArray === 'delete') {
        $modified = TRUE;
        $modifier->setSubnodes([]);
      }
      elseif (is_array($endArray)) {
        $modified = TRUE;
        $endString = $this->convertArrayToModifierString($endArray);
        $modifier->setSubnodes([new Leaf($endString)]);
      }
    }
    return new static($modified ? (string) $mutableRoot : $mutableRoot);
  }

  public function hasNodefaults(): bool {
    foreach ($this->findModifiers('smarty') as $modifier) {
      if (($modifier[1] ?? NULL) === 'nodefaults') {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function withoutNodefaults(): ExpressionDoc {
    return $this->filterModifiers(function ($modifier, ...$args) {
      if ($modifier === 'smarty') {
        if (($args[0] ?? NULL) === 'nodefaults') {
          return 'delete';
        }
      }
    });
  }

  protected function assertRootType(string $nodeType, string $detailType): void {
    $node = $this->root->findFirst($nodeType);
    if (!$node || $node->getDetailType() !== $detailType) {
      throw new \RuntimeException("Node is not of type $nodeType:$detailType");
    }
  }

  /**
   * @param \ParserGenerator\SyntaxTreeNode\Branch $modifier
   * @return string[]
   */
  protected function convertModifierToArray(Branch $modifier): array {
    $prefix = $modifier->findFirst('modifier_array') ? '@' : '';

    $modifierArr = [$prefix . (string) $modifier->findFirst('modifier_name')];
    foreach ($modifier->findAll('modifier_attribute') as $maNode) {
      $maText = substr((string) $maNode, 1);
      if ($maText[0] === '"' || $maText[0] === '`' || $maText[0] === "'") {
        $maText = substr($maText, 1, -1);
      }
      $modifierArr[] = $maText;
    }
    return $modifierArr;
  }

  protected function convertArrayToModifierString(array $modifierParts) {
    return '|' . implode(':', array_map(
        fn($part) => preg_match('/^@?[a-zA-Z0-9_]+$/', $part) ? $part : '"' . addslashes($part) . '"',
        $modifierParts
      ));
  }

}
