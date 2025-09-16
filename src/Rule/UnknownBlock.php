<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\CheckTagEvent;

/**
 * Any blocks like {my_block foo=$bar} should have a recognized block-name.
 */
class UnknownBlock {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (!$checkTag->isTagType('block_open') && !$checkTag->isTagType('block_close')) {
      return;
    }

    $blockName = $checkTag->tag->findFirst('block_name');
    switch ($blockName) {
      case 'assign':
      case 'capture':
      case 'crmAPI':
      case 'crmAttributes':
      case 'crmButton':
      case 'crmKey':
      case 'crmRegion':
      case 'crmPermission':
      case 'crmResURL':
      case 'crmScript':
      case 'crmScope':
      case 'crmSetting':
      case 'crmSigner':
      case 'crmSqlData':
      case 'crmStyle':
      case 'crmVersion':
      case 'crmURL':
      case 'copyIcon':
      case 'counter':
      case 'cycle':
      case 'continue':
      case 'debug':
      case 'docURL':
      case 'edit':
      case 'else':
      case 'foreach':
      case 'help':
      case 'icon':
      case 'if':
      case 'include':
      case 'isValueChange':
      case 'ldelim':
      case 'localize':
      case 'math':
      case 'privacyFlag':
      case 'rdelim':
      case 'section':
      case 'strip':
      case 'ts':
        return;

      default:
        $checkTag->advices[] = Advice::createProblem('WARNING: Unrecognized block', (string) $checkTag->tag);
        return;
    }
  }

}
