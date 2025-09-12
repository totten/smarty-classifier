<?php

namespace Civi\SmartyUp;

use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Reports {

  public static function getReportList(): array {
    return [
      'stanzas',
      'tags',
    ];
  }

  public static function stanzas(StyleInterface $output, Root $parsed): void {
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

  public static function tags(StyleInterface $output, Root $parsed): void {
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

  public static function advisor(StyleInterface $output, Root $parsed): void {
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

  public static function tree(StyleInterface $output, $parsed, string $prefix = ''): void {
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
    $io = new SymfonyStyle(new ArgvInput([]), new StreamOutput($fh));
    call_user_func([static::class, $name], $io, ...$args);
    fclose($fh);
  }

  public static function writeString(string $name, ...$args): string {
    $output = new BufferedOutput();
    $io = new SymfonyStyle(new ArgvInput([]), $output);
    call_user_func([static::class, $name], $io, ...$args);
    return $output->fetch();
  }

}
