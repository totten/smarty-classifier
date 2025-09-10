<?php

namespace Civi\SmartyClassifier;

use ParserGenerator\SyntaxTreeNode\Root;

class Classifier {

  public function classify(string $content): array {
    $grammarFile = dirname(__DIR__) . '/grammar.txt';
    $parser = new \ParserGenerator\Parser(file_get_contents($grammarFile));
    $parsed = $parser->parse($content);

    $this->printStanzas($parsed);
    // $this->printStanzaVariables($parsed);

    return [];
  }

  /**
   * @param $parsed
   *
   * @return \ParserGenerator\SyntaxTreeNode\Branch
   */
  protected function printStanzas(Root $parsed): \ParserGenerator\SyntaxTreeNode\Branch {
    foreach ($parsed->findAll('stanza') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        printf("[%s:%s] %s\n", $stanza->getType(),
          $stanza->getDetailType(),
          (string) $stanza);
      }
    }
    return $stanza;
  }

  /**
   * @param $parsed
   *
   * @return void
   */
  protected function printStanzaVariables(Root $parsed): void {
    foreach ($parsed->findAll('stanza:variable') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      printf("[%s] %s\n", $stanza->getType(), (string) $stanza);
      printf("  - VARIABLE: %s\n", $stanza->findFirst('variable')->getType());
      printf("  - MODIFIERS: %s\n", implode(", ", array_map(
        fn($v) => (string) $v,
        $stanza->findAll('modifiers')
      )));
    }
  }

}
