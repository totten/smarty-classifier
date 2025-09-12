<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\Advice\Advice;
use Civi\SmartyUp\Advisor\Advice\AdviceOk;
use Civi\SmartyUp\Advisor\Advice\AdviceProblem;
use Civi\SmartyUp\Advisor\Advice\AdviceSuggestion;
use Civi\SmartyUp\Rule\KnownBlockTag;
use ParserGenerator\SyntaxTreeNode\Branch;

class Advisor {

  protected $adviceListener;

  /**
   * @param callable $listener
   */
  public function __construct(callable $listener) {
    $this->adviceListener = $listener;
  }

  public function add(Advice $advice): void {
    call_user_func($this->adviceListener, $advice);
  }

  public function scanString(string $content): void {
    $parsedDoc = Services::createTopParser()->parse($content);
    $this->scanDocument($parsedDoc);
  }

  public function scanDocument($parsedDoc): void {
    $tagParser = Services::createTagParser();
    foreach ($parsedDoc->findAll('stanza:tag') as $k => $stanza) {
      $tagString = (string) $stanza;
      $parsedTag = $tagParser->parse($tagString);
      if (empty($parsedTag)) {
        $this->add(new AdviceProblem('PROBLEM: Unparsable tag', $tagString));
      }
      else {
        $this->scanTag($tagString, $parsedTag);
      }
    }
  }

  /**
   * @param string $tagString
   * @param \ParserGenerator\SyntaxTreeNode\Root $parsedTag
   *
   * @return void
   */
  protected function scanTag(string $tagString, \ParserGenerator\SyntaxTreeNode\Root $parsedTag): void {
    if ($parsedTag->findFirst('tag:condition')) {
      $this->add(new AdviceOk('OK', $tagString));
    }
    elseif ($parsedTag->findFirst('tag:block_close')) {
      // It's OK, and it's trivial. Don't bother recording...
    }
    elseif ($parsedTag->findFirst('tag:expression')) {
      $this->scanExpressionTag($tagString, $parsedTag);
    }
    elseif ($parsedTag->findFirst('tag:block_open')) {
      (new KnownBlockTag())->scanBlockTag($parsedTag, [$this, 'add']);
    }
    else {
      $this->add(new AdviceProblem('PROBLEM: Unrecognized tag contents', $tagString));
    }
  }

  /**
   * Scan a Smarty {$var} tag.
   *
   * @param string $tagString
   *   Ex: '{$variable}'
   *   Ex: '{$variable * 2}'
   *   Ex: '{$variable|escape:"html"}'
   * @param \ParserGenerator\SyntaxTreeNode\Branch $parsedTag
   * @return void
   */
  protected function scanExpressionTag(string $tagString, Branch $parsedTag) {
    // The basic rule is... for transition period, everything needs "nofilter".

    if ($parsedTag->findFirst('nofilter')) {
      $this->add(new AdviceOk('OK', $tagString));
      return;
    }

    // Convert '|smarty:nodefaults' to 'nofilter'
    if (str_contains($tagString, 'smarty:nodefaults')) {
      $suggest = preg_replace('/\|smarty:nodefaults\}$/', ' nofilter}', $tagString);
      $this->add(new AdviceSuggestion('PROBLEM: In Smarty v5, "smarty:nodefaults" does not work. Use "nofilter".', $tagString, [$suggest]));

      return;
    }

    // Can we figure out if this printing HTML data (e.g. `$form.my_button.html`) or text (e.g. `$api_result.display_name`)?
    if (str_starts_with($tagString, '{$form.')) {
      // This is clearly an HTML widget.
      $this->add(new AdviceSuggestion('PROBLEM: This looks like an HTML widget. Specify "nofilter".', $tagString, [$this->appendNofilter($tagString)]));
    }
    elseif (str_ends_with($tagString, '|escape}') || str_ends_with($tagString, '|escape:"html"}')) {
      // The data is already flagged as text. Preserve that. Add nofilter.
      $this->add(new AdviceSuggestion('PROBLEM: This has specific escaping rules. Specify "nofilter" to ensure they are respected.', $tagString, [$this->appendNofilter($tagString)]));
    }
    else {
      // The data is ambiguous. It could be HTML widget... or an integer... or free-form text...
      $this->add(new AdviceSuggestion('PROBLEM: It is unclear if the variable has HTML-markup or plain-text. Choose unambiguous notation:', $tagString, [
        preg_replace('/}$/', ' nofilter}', $tagString),
        preg_replace('/}$/', '|escape nofilter}', $tagString),
      ]));
    }
  }

  protected function appendNofilter(string $tagString): string {
    return preg_replace('/}$/', ' nofilter}', $tagString);
  }

}
