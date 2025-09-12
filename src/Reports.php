<?php

namespace Civi\SmartyClassifier;

use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;

class Reports {

  public static function getReportList(): array {
    return [
      'stanzas',
      'tags',
    ];
  }

  public static function stanzas(Root $parsed): string {
    $buffer = '';
    foreach ($parsed->findAll('stanza') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        $buffer .= "\n## " . $stanza->getType() . ":" . $stanza->getDetailType() . "\n" . (string) $stanza . "\n";
      }
    }
    return $buffer;
  }

  public static function tags(Root $parsed): string {
    $buffer = '';
    foreach ($parsed->findAll('stanza:tag') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        $buffer .= (string) $stanza . "\n";
      }
    }
    return $buffer;
  }

  public static function advisor(Root $parsed): string {
    $advisor = new Advisor();
    $advisor->scanDocument($parsed);

    $statuses = $advisor->getStatuses();
    $buffer = '';
    foreach ($statuses as $status) {
      $items = $advisor->getByStatus($status);
      if (empty($items)) {
        continue;
      }

      $buffer .= "\n## " . $status . "\n";
      foreach ($items as $item) {
        $buffer .= "- TAG: `" . $item['tag'] . "`\n";
        if (!empty($item['suggest'])) {
          if (is_string($item['suggest'])) {
            $buffer .= "  SUGGEST: " . $item['suggest'] . "\n";
          }
          elseif (is_array($item['suggest'])) {
            foreach ($item['suggest'] as $n => $suggest) {
              $buffer .= sprintf('  SUGGEST #%d: `%s`', 1 + $n, $suggest) . "\n";
            }
          }
        }
      }
    }

    return $buffer;

  }

  public static function tree($parsed, string $prefix = ''): string {
    $buffer = '';

    if ($parsed instanceof Branch) {
      $name = $parsed->getType() . ':' . $parsed->getDetailType();
      if (preg_match('/^&choices/', $name)) {
        $name = '&choices/XXXXXXXXXXXXXXXX';
      }
      $buffer .= $prefix . "- " . $name . "\n";
      foreach ($parsed->getSubnodes() as $subnode) {
        $buffer .= static::tree($subnode, $prefix . '  ');
      }
    }
    elseif ($parsed instanceof Leaf) {
      $buffer .= $prefix . "- [LEAF] " . json_encode($parsed->getContent()) . "\n";
    }
    else {
      $buffer .= $prefix . "- [UNKNOWN]\n";
    }

    return $buffer;
  }

  public static function writeFile(string $file, string $name, ...$args): void {
    $content = call_user_func([static::class, $name], ...$args);
    file_put_contents($file, $content);
  }

  public static function writeString(string $name, ...$args): string {
    return call_user_func([static::class, $name], ...$args);
  }

}
