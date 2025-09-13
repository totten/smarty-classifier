<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;

class UnparsedTag {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (empty($checkTag->tag)) {
      $checkTag->advices[] = Advice::createProblem('PROBLEM: Unparsable tag', $checkTag->original);
    }
  }

}
