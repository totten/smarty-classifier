<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseTagCommand extends Command {

  protected function configure() {
    $this->setName('parse-tag')
      ->setDescription('Parse a single smarty tag and show the parse tree');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $content = trim(file_get_contents('php://stdin'));

    $output->writeln("## Content");
    $output->writeln("$content\n");

    $output->writeln("## Tree");
    $parser = Services::createTagParser();
    $parsed = $parser->parse($content);
    $output->writeln(Reports::tree($parsed));
    $output->writeln("");
    return 0;
  }

}
