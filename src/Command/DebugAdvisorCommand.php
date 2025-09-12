<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;
use Civi\SmartyUp\SmartyUp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DebugAdvisorCommand extends Command {

  protected function configure() {
    $this->setName('debug:advisor')
      ->setDescription('Print advice for a list of tpl files')
      ->addArgument('files', InputArgument::IS_ARRAY, 'The tpl files to process');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = SmartyUp::io();
    $files = $input->getArgument('files');
    foreach ($files as $file) {
      $io->section($file);
      $content = file_get_contents($file);
      $parser = Services::createTopParser();
      $parsed = $parser->parse($content);
      Reports::advisor($io, $parsed);
      $io->writeln('');
    }
    return 0;
  }

}
