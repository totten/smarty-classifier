<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PrintTagsCommand extends Command {

  protected function configure() {
    $this->setName('print-tags')
      ->setDescription('Print tags for a list of tpl files')
      ->addArgument('files', InputArgument::IS_ARRAY, 'The tpl files to process');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $files = $input->getArgument('files');
    foreach ($files as $file) {
      $content = file_get_contents($file);
      $parser = Services::createTopParser();
      $parsed = $parser->parse($content);
      Reports::tags($output, $parsed);
    }
    return 0;
  }

}
