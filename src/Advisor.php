<?php

namespace Civi\SmartyUp;

use ParserGenerator\SyntaxTreeNode\Branch;

class Advisor {

  public $results = [];

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
        $this->add('PROBLEM: Unparsable tag', $tagString);
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
      $this->add('OK', $tagString);
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
      $this->add('PROBLEM: Unrecognized tag contents', $tagString);
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
      $this->add('OK', $tagString);
      return;
    }

    // Convert '|smarty:nodefaults' to 'nofilter'
    if (str_contains($tagString, 'smarty:nodefaults')) {
      $suggest = preg_replace('/\|smarty:nodefaults\}$/', ' nofilter}', $tagString);
      $this->add('PROBLEM: In Smarty v5, "smarty:nodefaults" does not work. Use "nofilter".', $tagString,
        ($suggest === $tagString) ? NULL : $suggest);
      return;
    }

    // Can we figure out if this printing HTML data (e.g. `$form.my_button.html`) or text (e.g. `$api_result.display_name`)?
    if (str_starts_with($tagString, '{$form.')) {
      // This is clearly an HTML widget.
      $this->add('PROBLEM: This looks like an HTML widget. Specify "nofilter".', $tagString,
        $this->appendNofilter($tagString));
    }
    elseif (str_ends_with($tagString, '|escape}') || str_ends_with($tagString, '|escape:"html"}')) {
      // The data is already flagged as text. Preserve that. Add nofilter.
      $this->add('PROBLEM: This has specific escaping rules. Specify "nofilter" to ensure they are respected.', $tagString,
        $this->appendNofilter($tagString));
    }
    else {
      // The data is ambiguous. It could be HTML widget... or an integer... or free-form text...
      $this->add('PROBLEM: It is unclear if the variable has HTML-markup or plain-text. Choose unambiguous notation:', $tagString, [
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
      case 'crmRegion':
      case 'foreach':
      case 'help':
      case 'include':
        $this->add('OK', $tagString);
        return;

      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          $this->add('WARNING: Block has printable, dynamic parameters', $tagString);
        }
        else {
          $this->add('OK', $tagString);
        }
        return;

      default:
        $this->add('WARNING: Unrecognized block', $tagString);
    }
  }

  public function add(string $status, string $tagString, $suggest = NULL): void {
    $id = md5($status . chr(0) . $tagString);
    $this->results[$id] = [
      'status' => $status,
      'tag' => $tagString,
      'suggest' => $suggest,
    ];
  }

  public function getByStatus(string $status): array {
    return array_filter($this->results, fn($r) => $r['status'] === $status);
  }

  public function getStatuses(): array {
    return array_unique(array_column($this->results, 'status'));
  }

}
