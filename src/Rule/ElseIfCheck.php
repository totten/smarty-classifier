<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;

/**
 * The expresison '{else if ...}' is not portable.
 */
class ElseIfCheck {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (!$checkTag->isTagType('condition')) {
      return;
    }

    $verb = $checkTag->tag->findFirst('condition_verb');
    if (((string) $verb) === 'else if') {
      $checkTag->advices[] = Advice::createSuggestion('PROBLEM: {else if} is not portable', (string) $checkTag->tag, [
        preg_replace('/^\{else if /', '{elseif ', $checkTag->original),
      ]);
    }
  }

}
