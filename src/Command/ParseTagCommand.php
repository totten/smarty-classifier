<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;

class ParseTagCommand {

  public function run(array $argv): int {
    $content = trim(file_get_contents('php://stdin'));

    echo "## Content\n";
    echo "$content\n\n";

    echo "## Tree\n";
    $parser = Services::createTagParser();
    $parsed = $parser->parse($content);
    echo Reports::tree($parsed);
    echo "\n\n";
    return 0;
  }

}
