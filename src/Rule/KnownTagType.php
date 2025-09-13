<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;

/**
 *
 */
class KnownTagType {

  public function checkTag(CheckTagEvent $checkTag): void {
    $tagType = $checkTag->getTagType();
    if (!in_array($tagType, ['block_open', 'block_close', 'condition', 'expression', NULL], TRUE)) {
      $checkTag->advices[] = Advice::createProblem("PROBLEM: Unrecognized tag type ($tagType)", (string) $checkTag->tag);
    }
  }

}
