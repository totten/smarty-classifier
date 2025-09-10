<?php

namespace Civi\SmartyClassifier;

class Classifier {

  public function classify(string $content): array {
    $grammarFile = dirname(__DIR__) . '/grammar.txt';
    $parser = new \ParserGenerator\Parser(file_get_contents($grammarFile));
    $parsed = $parser->parse($content);

    // $this->printStanzas($parsed);
    $this->printStanzaVariables($parsed);

    return [];
  }

  /**
   * @param $parsed
   *
   * @return \ParserGenerator\SyntaxTreeNode\Branch
   */
  protected function printStanzas($parsed): \ParserGenerator\SyntaxTreeNode\Branch {
    foreach ($parsed->findAll('stanza') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      foreach ($stanza->getSubnodes() as $child) {
        $rendered = (string) $child;
        if (trim($rendered) !== '') {
          printf("[%s] %s\n", $child->getType(), json_encode((string) $child));
        }
      }
    }
    return $stanza;
  }

  /**
   * @param $parsed
   *
   * @return void
   */
  protected function printStanzaVariables($parsed): void {
    foreach ($parsed->findAll('stanza_variable') as $k => $stanza) {
      printf("[%s] %s\n", $stanza->getType(), json_encode((string) $stanza));
    }
  }

}
