<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;
use ParserGenerator\SyntaxTreeNode\Leaf;

/**
 * The expression '{else if ...}' is not portable.
 */
class LowerCaseSymbols {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (!$checkTag->tag) {
      return;
    }

    $start = (string) $checkTag->tag;
    foreach ($checkTag->tag->findAll('bareword') as $barewordTag) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $barewordTag */
      $bareword = (string) $barewordTag;
      if (in_array($bareword, ['TRUE', 'FALSE', 'NULL'], TRUE)) {
        $barewordTag->setSubnodes([new Leaf(strtolower($bareword))]);
      }
    }

    $final = (string) $checkTag->tag;
    if ($final !== $start) {
      $checkTag->advices[] = Advice::createSuggestion('PROBLEM: Boolean and NULL must be lowercase in Smarty', $start, [
        $final,
      ]);
    }

  }

}
