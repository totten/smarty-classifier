<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use ParserGenerator\SyntaxTreeNode\Branch;

/**
 * Any blocks like {my_block foo=$bar} should have a recognized block-name.
 */
class KnownBlockTag {

  public function scanBlockTag(Branch $parsedTag): array {
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
        return [];

      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          return [Advice::createProblem('WARNING: Block has printable, dynamic parameters', $tagString)];
        }
        else {
          return [];
        }

      default:
        return [Advice::createProblem('WARNING: Unrecognized block', $tagString)];
    }
  }

}
