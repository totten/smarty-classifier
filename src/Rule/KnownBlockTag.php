<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice\AdviceOk;
use Civi\SmartyUp\Advisor\Advice\AdviceProblem;
use ParserGenerator\SyntaxTreeNode\Branch;

/**
 * Any blocks like {my_block foo=$bar} should have a recognized block-name.
 */
class KnownBlockTag {

  public function scanBlockTag(Branch $parsedTag, $add) {
    $tagString = (string) $parsedTag;

    $blockName = $parsedTag->findFirst('block_name');
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
      case 'include':
      case 'strip':
        $add(new AdviceOk('OK', $tagString));
        return;

      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          $add(new AdviceProblem('WARNING: Block has printable, dynamic parameters', $tagString));
        }
        else {
          $add(new AdviceOk('OK', $tagString));
        }
        return;

      default:
        $add(new AdviceProblem('WARNING: Unrecognized block', $tagString));
        return;
    }
  }

}
