<?php

namespace Civi\SmartyUp;

use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;

class Builder {

  /**
   * Append the "nofilter" flag to the {$expression}.
   *
   * @param \ParserGenerator\SyntaxTreeNode\Root $expressionRoot
   *   Ex: `{$foo.bar|whiz:bang}`
   * @return \ParserGenerator\SyntaxTreeNode\Root
   *   Ex: `{$foo.bar|whiz:bang nofilter}`
   */
  public static function withNofilter(Root $expressionRoot): Root {
    static::assertRootType($expressionRoot, 'tag', 'expression');

    $mutableRoot = $expressionRoot->copy();
    $tag = $mutableRoot->findFirst('tag:expression');
    $tag->setSubnode(2, new Branch('&choices', NULL, [
      new Branch('sp', NULL, [new Leaf(' ')]),
      new Branch('nofilter', NULL, [new Leaf('nofilter')]),
    ]));
    return $mutableRoot;
  }

  /**
   * Append another modifier to the {$expression}.
   *
   * @param \ParserGenerator\SyntaxTreeNode\Root $tagRoot
   *   Ex: `{$foo.bar|whiz:bang nofilter}`
   * @param string $text
   *   Ex: '|escape:url'
   * @return \ParserGenerator\SyntaxTreeNode\Root
   *   Ex: `{$foo.bar|whiz:bang|escape:url nofilter}`
   */
  public static function withModifier(Root $tagRoot, string $text): Root {
    static::assertRootType($tagRoot, 'tag', 'expression');

    $mutableRoot = $tagRoot->copy();
    $modifierList = $mutableRoot->findFirst('list:modifier');
    $modifierList->setSubnodes([
      ...$modifierList->getSubnodes(),
      new Branch('modifier', 0, [new Leaf($text)]),
    ]);
    return $mutableRoot;
  }

  protected static function assertRootType(Root $root, string $nodeType, string $detailType): void {
    $node = $root->findFirst($nodeType);
    if (!$node || $node->getDetailType() !== $detailType) {
      throw new \RuntimeException("Node is not of type $nodeType:$detailType");
    }
  }

}
