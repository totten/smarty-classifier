<?php

namespace Civi\SmartyClassifier;

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
    if (str_contains($tagString, 'smarty:nodefaults')) {
      $this->add('PROBLEM: Change "smarty:nodefaults" to "nofilter" for portability', $tagString);
      return;
    }

    if ($parsedTag->findFirst('nofilter')) {
      $this->add('OK', $tagString);
      return;
    }

    $this->add('PROBLEM: Use "nofilter" or "|escape nofilter" for portability', $tagString);
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
    $blockName = $parsedTag->findFirst('blockname');
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

  public function add(string $status, string $tagString, ?string $message = NULL): void {
    $id = md5($status . chr(0) . $tagString);
    $this->results[$id] = [
      'status' => $status,
      'tag' => $tagString,
      'message' => $message,
    ];
  }

  public function getByStatus(string $status): array {
    return array_filter($this->results, fn($r) => $r['status'] === $status);
  }

  public function getStatuses(): array {
    return array_unique(array_column($this->results, 'status'));
  }

}
