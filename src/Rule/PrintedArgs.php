<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;

/**
 * Some blocks accept dynamic parameters which can be subsequently printed. We want to check escaping
 * rules on these.
 */
class PrintedArgs {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (!$checkTag->isTagType('block_open')) {
      return;
    }

    $tagString = (string) $checkTag->tag;

    $blockName = $checkTag->tag->findFirst('block_name');
    switch ($blockName) {
      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          $checkTag->advices[] = Advice::createProblem('WARNING: Block has printable, dynamic parameters', $tagString);
        }
        return;
    }
  }

}
