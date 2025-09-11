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
        $this->add('unparseable', $tagString);
        return;
      }

      if ($parsedTag->findFirst('tag:condition')) {
        $this->add('ok', $tagString);
      }
      elseif ($parsedTag->findFirst('tag:block_close')) {
        // It's OK, and it's trivial. Don't bother recording...
      }
      elseif ($parsedTag->findFirst('tag:expression')) {
        $this->scanExpression($tagString, $parsedTag);
      }
      elseif ($parsedTag->findFirst('tag:block_open')) {
        $this->scanBlock($tagString, $parsedTag);
      }
      else {
        $this->add('unrecognized', $tagString);
      }
    }
  }

  protected function scanExpression(string $tagString, Branch $parsedTag) {
    if (str_contains($tagString, 'smarty:nodefaults')) {
      $this->add('FIXME: smarty:nodefaults is not portable', $tagString);
      return;
    }

    if ($parsedTag->findFirst('nofilter')) {
      $this->add('ok', $tagString);
      return;
    }

    $this->add('FIXME: Use "nofilter" or "|escape nofilter"', $tagString);
  }

  protected function scanBlock(string $tagString, Branch $parsedTag) {
    $blockName = $parsedTag->findFirst('blockname');
    switch ($blockName) {
      case 'assign':
      case 'capture':
      case 'crmAPI':
      case 'crmRegion':
      case 'foreach':
      case 'help':
      case 'include':
        $this->add('ok', $tagString);
        return;

      case 'docURL':
      case 'ts':
        if (str_contains($tagString, '$')) {
          $this->add('block with dynamic parameters', $tagString);
        }
        else {
          $this->add('ok', $tagString);
        }
        return;

      default:
        $this->add('unrecognized block', $tagString);
    }
  }

  public function add(string $status, string $tagString, ?string $message = NULL): void {
    $this->results[] = [
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
