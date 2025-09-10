<?php

namespace Civi\SmartyClassifier;

use ParserGenerator\SyntaxTreeNode\Root;

class Classifier {

  public function run(string $content): void {
    $grammarDir = dirname(__DIR__);
    $grammar = file_get_contents("$grammarDir/grammar/top.txt")
      . "\n" . file_get_contents("$grammarDir/grammar/common.txt");

    $parser = new \ParserGenerator\Parser($grammar);
    $parsed = $parser->parse($content);

    $this->printStanzas($parsed);
    // $this->printStanzaVariables($parsed);
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
        printf("\n## %s:%s\n%s\n", $stanza->getType(),
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
      printf("EXPRESSION: %s\n", $stanza->getType(), (string) $stanza);
      printf("  - VARIABLE: %s\n", $stanza->findFirst('variable')->getType());
      printf("  - MODIFIERS: %s\n", implode(", ", array_map(
        fn($v) => (string) $v,
        $stanza->findAll('modifiers')
      )));
    }
  }

}
