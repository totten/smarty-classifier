<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\AdviceListener;
use ParserGenerator\SyntaxTreeNode\Branch;

class Advisor {

  protected AdviceListener $listener;

  /**
   * @param \Civi\SmartyUp\Advisor\AdviceListener $listener
   */
  public function __construct(AdviceListener $listener) {
    $this->listener = $listener;
  }

  public function add(string $status, string $message, string $tagString, $suggest = NULL): void {
    $this->listener->add($status, $message, $tagString, $suggest);
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
        $this->add('ok', 'PROBLEM: Unparsable tag', $tagString);
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
      $this->add('ok', 'OK', $tagString);
    }
    elseif ($parsedTag->findFirst('tag:block_close')) {
      // It's OK, and it's trivial. Don't bother recording...
    }
    elseif ($parsedTag->findFirst('tag:expression')) {
      $this->scanExpressionTag($tagString, $parsedTag);
    }
    elseif ($parsedTag->findFirst('tag:block_open')) {
      $this->scanBlockTag($tagString, $parsedTag);
    }
    else {
      $this->add('problem', 'PROBLEM: Unrecognized tag contents', $tagString);
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
      $this->add('ok', 'OK', $tagString);
      return;
    }

    // Convert '|smarty:nodefaults' to 'nofilter'
    if (str_contains($tagString, 'smarty:nodefaults')) {
      $suggest = preg_replace('/\|smarty:nodefaults\}$/', ' nofilter}', $tagString);
      $this->add('problem', 'PROBLEM: In Smarty v5, "smarty:nodefaults" does not work. Use "nofilter".', $tagString,
        ($suggest === $tagString) ? NULL : $suggest);
      return;
    }

    // Can we figure out if this printing HTML data (e.g. `$form.my_button.html`) or text (e.g. `$api_result.display_name`)?
    if (str_starts_with($tagString, '{$form.')) {
      // This is clearly an HTML widget.
      $this->add('problem', 'PROBLEM: This looks like an HTML widget. Specify "nofilter".', $tagString,
        $this->appendNofilter($tagString));
    }
    elseif (str_ends_with($tagString, '|escape}') || str_ends_with($tagString, '|escape:"html"}')) {
      // The data is already flagged as text. Preserve that. Add nofilter.
      $this->add('problem', 'PROBLEM: This has specific escaping rules. Specify "nofilter" to ensure they are respected.', $tagString,
        $this->appendNofilter($tagString));
    }
    else {
      // The data is ambiguous. It could be HTML widget... or an integer... or free-form text...
      $this->add('problem', 'PROBLEM: It is unclear if the variable has HTML-markup or plain-text. Choose unambiguous notation:', $tagString, [
        preg_replace('/}$/', ' nofilter}', $tagString),
        preg_replace('/}$/', '|escape nofilter}', $tagString),
      ]);
    }
  }

  protected function appendNofilter(string $tagString): string {
    return preg_replace('/}$/', ' nofilter}', $tagString);
  }

  /**
   * Scan a Smarty {block} tag.
   *
   * @param string $tagString
   *   Ex: '{ts}'
   *   Ex: '{ts 1=$contact.display_name}'
   * @param \ParserGenerator\SyntaxTreeNode\Branch $parsedTag
   * @return void
   */
  protected function scanBlockTag(string $tagString, Branch $parsedTag) {
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
        $this->add('ok', 'OK', $tagString);
        return;

      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          $this->add('problem', 'WARNING: Block has printable, dynamic parameters', $tagString);
        }
        else {
          $this->add('ok', 'OK', $tagString);
        }
        return;

      default:
        $this->add('problem', 'WARNING: Unrecognized block', $tagString);
    }
  }

}
