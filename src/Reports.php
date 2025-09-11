<?php

namespace Civi\SmartyClassifier;

use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;

class Reports {

  public static function getReportList(): array {
    return [
      'stanzas',
      'tags',
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

  public static function advisor($fh, Root $parsed): void {
    $advisor = new Advisor();
    $advisor->scanDocument($parsed);

    $statuses = $advisor->getStatuses();
    foreach ($statuses as $status) {
      $items = $advisor->getByStatus($status);
      if (empty($items)) {
        continue;
      }

      fprintf($fh, "\n## %s:\n", $status);
      foreach ($items as $item) {
        fprintf($fh, "- TAG: %s\n", $item['tag']);
        if (!empty($item['message'])) {
          fprintf($fh, "  MESSAGE: %s\n", $item['message']);
        }
      }
    }

  }

  public static function tree($fh, $parsed, $prefix = ''): void {
    if ($parsed instanceof \ParserGenerator\SyntaxTreeNode\Branch) {
      $name = $parsed->getType() . ':' . $parsed->getDetailType();
      if (preg_match('/^&choices/', $name)) {
        $name = '&choices/XXXXXXXXXXXXXXXX';
      }
      fprintf($fh, "%s- %s\n", $prefix, $name);
      foreach ($parsed->getSubnodes() as $subnode) {
        static::tree($fh, $subnode, $prefix . '  ');
      }
    }
    elseif ($parsed instanceof Leaf) {
      fprintf($fh, "%s- [LEAF] %s\n", $prefix, json_encode($parsed->getContent()));
    }
  }

  public static function writeFile(string $file, string $name, ...$args): void {
    $fh = fopen($file, 'w');
    call_user_func([static::class, $name], $fh, ...$args);
    fclose($fh);
  }

  public static function writeString(string $name, ...$args): string {
    $fh = fopen('php://temp', 'r+');
    call_user_func([static::class, $name], $fh, ...$args);
    rewind($fh);
    $result = stream_get_contents($fh);
    fclose($fh);
    return $result;
  }

}
