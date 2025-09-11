<?php

namespace Civi\SmartyClassifier;

use ParserGenerator\SyntaxTreeNode\Root;

class Reports {

  public static function getReportList(): array {
    return [
      '.stanza.txt' => [Reports::class, 'stanzas'],
      '.tags.txt' => [Reports::class, 'tags'],
    ];
  }

  public static function stanzas($fh, Root $parsed): void {
    foreach ($parsed->findAll('stanza') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        fprintf($fh, "\n## %s:%s\n%s\n", $stanza->getType(),
          $stanza->getDetailType(),
          (string) $stanza);
      }
    }
  }

  public static function tags($fh, Root $parsed): void {
    foreach ($parsed->findAll('stanza:tag') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        fprintf($fh, "%s\n", (string) $stanza);
      }
    }
  }

}
