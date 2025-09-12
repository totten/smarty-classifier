<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Services;
use Civi\SmartyUp\Reports;
use Civi\SmartyUp\SmartyUp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ParseTagCommand extends Command {

  protected function configure() {
    $this->setName('parse')
      ->setDescription('Parse the tags in Smarty document');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $content = trim(file_get_contents('php://stdin'));
    $tagParser = Services::createTagParser();
    $topParser = Services::createTopParser();

    $io = SmartyUp::io();
    $io->title('Parse Smarty');

    $stanzas = $topParser->parse($content)->findAll('stanza');
    $tags = $topParser->parse($content)->findAll('stanza:tag');

    $io->text(sprintf("Found %d stanza(s) with %d tag(s).", count($stanzas), count($tags)));

    $io->newLine();

    foreach ($stanzas as $num => $stanza) {
      $section = "Stanza #" . (1 + $num) . ": " . ucfirst($stanza->getDetailType());
      $stanzaText = (string) $stanza;

      $io->section($section);
      $io->text($stanzaText);

      if ($stanza->getDetailType() === 'tag') {
        $io->section("$section: Tree");
        $parsed = $tagParser->parse($stanzaText);
        Reports::tree($io, $parsed);
      }

      $io->newLine();
    }

    return 0;
  }

}
