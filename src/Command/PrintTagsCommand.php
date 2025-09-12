<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;

class PrintTagsCommand {

  public function run(array $argv): int {
    $files = $argv;
    array_shift($files);
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $parser = Services::createTopParser();
      $parsed = $parser->parse($content);
      echo Reports::tags($parsed);
    }
    return 0;
  }

}
