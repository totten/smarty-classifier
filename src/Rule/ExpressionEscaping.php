<?php

namespace Civi\SmartyUp\Rule;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\ExpressionDoc;
use Civi\SmartyUp\CheckTagEvent;
use ParserGenerator\SyntaxTreeNode\Branch;

/**
 * Any blocks like {$foo} should have explicit escaping rules.
 */
class ExpressionEscaping {

  public function checkTag(CheckTagEvent $checkTag): void {
    if (!$checkTag->isTagType('expression')) {
      return;
    }

    if ($a = $this->scanExpressionTag($checkTag->tag)) {
      $checkTag->advices[] = $a;
    }
  }

  /**
   * Scan a Smarty {$var} tag.
   *
   * @param \ParserGenerator\SyntaxTreeNode\Branch $parsedTag
   *   Parsed version of an expression, such as:
   *     Ex: '{$variable}'
   *     Ex: '{$variable * 2}'
   *     Ex: '{$variable|escape:"html"}'
   *     Ex: '{2 + 2}'
   * @return ?Advice
   */
  protected function scanExpressionTag(Branch $parsedTag) {
    // The basic rule is... for transition period, everything needs "nofilter".

    $tagString = (string) $parsedTag;

    if ($parsedTag->findFirst('nofilter')) {
      // OK, no problem!
      return NULL;
    }

    $doc = new ExpressionDoc($parsedTag);

    // Convert '|smarty:nodefaults' to 'nofilter'
    if ($doc->hasNodefaults()) {
      return Advice::createSuggestion('PROBLEM: In Smarty v5, "smarty:nodefaults" does not work. Use "nofilter".', $tagString, [
        (string) $doc->withoutNodefaults()->withNofilter(),
      ]);
    }

    // Can we figure out if this printing HTML data (e.g. `$form.my_button.html`) or text (e.g. `$api_result.display_name`)?
    if (str_starts_with($tagString, '{$form.')) {
      // This is clearly an HTML widget.
      return Advice::createSuggestion('PROBLEM: This looks like an HTML widget. Specify "nofilter".', $tagString, [
        (string) $doc->withNofilter(),
      ]);
    }
    elseif ($doc->findModifiers('escape') || $doc->findModifiers('purify')) {
      // The data is already flagged for special escaping. Preserve that. Add nofilter.
      return Advice::createSuggestion('PROBLEM: This has specific escaping rules. Specify "nofilter" to ensure they are respected.', $tagString, [
        (string) $doc->withNofilter(),
      ]);
    }
    else {
      // The data is ambiguous. It could be HTML widget... or an integer... or free-form text...
      return Advice::createSuggestion('PROBLEM: It is unclear if the variable has HTML-markup or plain-text. Choose unambiguous notation:', $tagString, [
        (string) $doc->withNofilter(),
        (string) $doc->withNofilter()->withModifier('|escape'),
      ]);
    }
  }

}
