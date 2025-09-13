<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\Advice;
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

  public function add(?Advice $advice): void {
    if ($advice) {
      call_user_func($this->adviceListener, $advice);
    }
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
      foreach ($this->scanTag($tagString, $parsedTag) as $advice) {
        $this->add($advice);
      }
    }
  }

  /**
   * @param string $tagString
   * @param \ParserGenerator\SyntaxTreeNode\Root|false $parsedTag
   *
   * @return void
   */
  protected function scanTag(string $tagString, $parsedTag): array {
    $advices = [];

    if (empty($parsedTag)) {
      $advices[] = Advice::createProblem('PROBLEM: Unparsable tag', $tagString);
    }
    elseif ($parsedTag->findFirst('tag:condition')) {
      // OK
    }
    elseif ($parsedTag->findFirst('tag:block_close')) {
      // It's OK, and it's trivial. Don't bother recording...
      return [];
    }
    elseif ($parsedTag->findFirst('tag:expression')) {
      if ($a = $this->scanExpressionTag($tagString, $parsedTag)) {
        $advices[] = $a;
      }
    }
    elseif ($parsedTag->findFirst('tag:block_open')) {
      foreach ((new KnownBlockTag())->scanBlockTag($parsedTag) as $a) {
        $advices[] = $a;
      }
    }
    else {
      $advices[] = Advice::createProblem('PROBLEM: Unrecognized tag contents', $tagString);
    }

    if (empty($advices)) {
      $advices[] = Advice::createOK('OK', $tagString);
    }

    return $advices;
  }

  /**
   * Scan a Smarty {$var} tag.
   *
   * @param string $tagString
   *   Ex: '{$variable}'
   *   Ex: '{$variable * 2}'
   *   Ex: '{$variable|escape:"html"}'
   * @param \ParserGenerator\SyntaxTreeNode\Branch $parsedTag
   * @return ?Advice
   */
  protected function scanExpressionTag(string $tagString, Branch $parsedTag) {
    // The basic rule is... for transition period, everything needs "nofilter".

    if ($parsedTag->findFirst('nofilter')) {
      // OK, no problem!
      return NULL;
    }

    // Convert '|smarty:nodefaults' to 'nofilter'
    if (str_contains($tagString, 'smarty:nodefaults')) {
      $suggest = preg_replace('/\|smarty:nodefaults\}$/', ' nofilter}', $tagString);
      return Advice::createSuggestion('PROBLEM: In Smarty v5, "smarty:nodefaults" does not work. Use "nofilter".', $tagString, [$suggest]);
    }

    // Can we figure out if this printing HTML data (e.g. `$form.my_button.html`) or text (e.g. `$api_result.display_name`)?
    if (str_starts_with($tagString, '{$form.')) {
      // This is clearly an HTML widget.
      return Advice::createSuggestion('PROBLEM: This looks like an HTML widget. Specify "nofilter".', $tagString, [$this->appendNofilter($tagString)]);
    }
    elseif (str_ends_with($tagString, '|escape}') || str_ends_with($tagString, '|escape:"html"}')) {
      // The data is already flagged as text. Preserve that. Add nofilter.
      return Advice::createSuggestion('PROBLEM: This has specific escaping rules. Specify "nofilter" to ensure they are respected.', $tagString, [$this->appendNofilter($tagString)]);
    }
    else {
      // The data is ambiguous. It could be HTML widget... or an integer... or free-form text...
      return Advice::createSuggestion('PROBLEM: It is unclear if the variable has HTML-markup or plain-text. Choose unambiguous notation:', $tagString, [
        preg_replace('/}$/', ' nofilter}', $tagString),
        preg_replace('/}$/', '|escape nofilter}', $tagString),
      ]);
    }
  }

  protected function appendNofilter(string $tagString): string {
    return preg_replace('/}$/', ' nofilter}', $tagString);
  }

}
