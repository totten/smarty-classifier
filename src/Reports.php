<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\AdviceCollector;
use ParserGenerator\SyntaxTreeNode\Branch;
use ParserGenerator\SyntaxTreeNode\Leaf;
use ParserGenerator\SyntaxTreeNode\Root;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Reports {

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
    $advice = new AdviceCollector();
    $advisor = new Advisor([$advice, 'addAdvice']);
    $advisor->scanDocument($parsed);

    $statuses = $advice->getDistinct('message');
    $buffer = '';
    foreach ($statuses as $message) {
      $items = $advice->filter(fn($r) => $r['message'] === $message);
      if (empty($items)) {
        continue;
      }

      $buffer .= "\n## " . $message . "\n";
      foreach ($items as $item) {
        $buffer .= "- TAG: `" . $item['tag'] . "`\n";
        if (!empty($item['suggest'])) {
          $suggests = (array) $item['suggest'];
          if (count($suggests) === 1) {
            $buffer .= "  SUGGEST: " . $item['suggest'][0] . "\n";
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

  public static function tree(StyleInterface $io, $parsed, string $prefix = '', ?int $num = NULL): void {
    $numStr = ($num === NULL) ? '' : " $num:";

    if ($parsed instanceof Branch) {
      $name = '<comment>' . $parsed->getType() . '</comment>';
      $name = preg_replace(';&choices/[0-9a-f]+;', '&choices/XXXXXXXXXXXXXXXX', $name);

      if (!empty($parsed->getDetailType())) {
        $name .= ':<comment>' . $parsed->getDetailType() . '</comment>';
      }
      $io->writeln($prefix . "-$numStr [$name] " . static::formatLiteralString((string) $parsed));
      $n = 0;
      foreach ($parsed->getSubnodes() as $subnode) {
        static::tree($io, $subnode, $prefix . '    ', $n++);
      }
    }
    elseif ($parsed instanceof Leaf) {
      $io->writeln($prefix . "-$numStr " . static::formatLiteralString((string) $parsed));
    }
    else {
      $io->writeln($prefix . "-$numStr [<error>UNKNOWN</error>]");
    }
  }

  private static function formatLiteralString(string $string): string {
    // $j = json_encode($string, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    // return "\"<info>" . substr($j, 1, -1) . "</info>\"";
    return "'<info>" . $string . "</info>'";
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
