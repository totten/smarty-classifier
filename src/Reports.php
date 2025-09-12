<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Console\FileHandleOutput;
use Civi\SmartyUp\Console\StringOutput;
use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use Symfony\Component\Console\Output\OutputInterface;

class Reports {

  public static function getReportList(): array {
    return [
      'stanzas',
      'tags',
    ];
  }

  public static function stanzas(OutputInterface $output, Root $parsed): void {
    $buffer = '';
    foreach ($parsed->findAll('stanza') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        $buffer .= "\n## " . $stanza->getType() . ":" . $stanza->getDetailType() . "\n" . (string) $stanza . "\n";
      }
    }
    $output->write($buffer);
  }

  public static function tags(OutputInterface $output, Root $parsed): void {
    $buffer = '';
    foreach ($parsed->findAll('stanza:tag') as $k => $stanza) {
      /** @var \ParserGenerator\SyntaxTreeNode\Branch $stanza */
      $string = (string) $stanza;
      if (trim($string) !== '') {
        $buffer .= (string) $stanza . "\n";
      }
    }
    $output->write($buffer);
  }

  public static function advisor(OutputInterface $output, Root $parsed): void {
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

    $output->write($buffer);
  }

  public static function tree(OutputInterface $output, $parsed, string $prefix = ''): void {
    if ($parsed instanceof Branch) {
      $name = $parsed->getType() . ':' . $parsed->getDetailType();
      if (preg_match('/^&choices/', $name)) {
        $name = '&choices/XXXXXXXXXXXXXXXX';
      }
      $output->writeln($prefix . "- " . $name);
      foreach ($parsed->getSubnodes() as $subnode) {
        static::tree($output, $subnode, $prefix . '  ');
      }
    }
    elseif ($parsed instanceof Leaf) {
      $output->writeln($prefix . "- [LEAF] " . json_encode($parsed->getContent()));
    }
    else {
      $output->writeln($prefix . "- [UNKNOWN]");
    }
  }

  public static function writeFile(string $file, string $name, ...$args): void {
    $fh = fopen($file, 'w');
    $output = new FileHandleOutput($fh);
    call_user_func([static::class, $name], $output, ...$args);
    fclose($fh);
  }

  public static function writeString(string $name, ...$args): string {
    $output = new StringOutput();
    call_user_func([static::class, $name], $output, ...$args);
    return $output->flush();
  }

}
