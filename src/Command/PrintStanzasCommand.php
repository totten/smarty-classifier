<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;

class PrintStanzasCommand {

  public function getUsage(): string {
    return 'print-stanzas <tpl-files...>';
  }

  public function run(array $argv): int {
    $files = $argv;
    array_shift($files);
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $parser = Services::createTopParser();
      $parsed = $parser->parse($content);
      echo Reports::stanzas($parsed);
    }
    return 0;
  }

}
