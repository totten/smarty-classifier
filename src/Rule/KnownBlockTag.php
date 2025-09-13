<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;

/**
 * Any blocks like {my_block foo=$bar} should have a recognized block-name.
 */
class KnownBlockTag {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (!$checkTag->isTagType('block_open') && !$checkTag->isTagType('block_close')) {
      return;
    }

    $tagString = (string) $checkTag->tag;

    $blockName = $checkTag->tag->findFirst('block_name');
    switch ($blockName) {
      case 'assign':
      case 'capture':
      case 'crmAPI':
      case 'crmButton':
      case 'crmRegion':
      case 'crmURL':
      case 'cycle':
      case 'continue':
      case 'else':
      case 'foreach':
      case 'help':
      case 'icon':
      case 'if':
      case 'include':
      case 'strip':
        return;

      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          $checkTag->advices[] = Advice::createProblem('WARNING: Block has printable, dynamic parameters', $tagString);
        }
        return;

      default:
        $checkTag->advices[] = Advice::createProblem('WARNING: Unrecognized block', $tagString);
        return;
    }
  }

}
