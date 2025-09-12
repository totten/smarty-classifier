<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Files;
use Civi\SmartyUp\Process;
use Civi\SmartyUp\Reports;
use Civi\SmartyUp\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ScanExamplesCommand extends Command {

  protected function configure() {
    $prj = dirname(dirname(__DIR__));
    $inDir = "$prj/examples/input";
    $outDir = "$prj/examples/output";

    $this->setName('scan-examples')
      ->setDescription('Scan example tpl files and generate reports')
      ->addOption('input-dir', 'i', InputOption::VALUE_REQUIRED, 'The input directory', $inDir)
      ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'The output directory', $outDir)
      ->addOption('name', 'N', InputOption::VALUE_REQUIRED, 'Filter by tpl name', '*.tpl');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $inputBaseDir = rtrim($input->getOption('input-dir'), '/');
    $outputBaseDir = rtrim($input->getOption('output-dir'), '/');

    Services::createTopParser();
    Services::createTagParser();

    $files = (new Finder)->in($inputBaseDir)->files()->name($input->getOption('name'));
    $errors = 0;

    foreach ($files as $fileObj) {
      /** @var \SplFileInfo $fileObj */
      $inputFile = (string) $fileObj;
      $relativeFile = substr($inputFile, strlen($inputBaseDir) + 1);
      $outputDir = $outputBaseDir . '/' . $relativeFile . '.d';
      try {
        Process::doAsChild(fn() => $this->processFile($inputFile, $outputDir, $output));
      }
      catch (\Throwable $e) {
        $output->writeln(sprintf("\nERROR (%s): %s\n\n", $inputFile, $e->getMessage()));
        $errors++;
      }
    }

    $output->writeln("");
    return $errors === 0 ? 0 : 1;
  }

  private function processFile(string $inputFile, string $outputDir, OutputInterface $output): void {
    $output->writeln(sprintf("Process <comment>%s</comment> => <comment>%s</comment>", $inputFile, $outputDir));
    // $output->writeln(sprintf("Process <comment>%s</comment> => <comment>%s</comment> (<comment>%s</comment>)", $inputFile, $outputDir, number_format(memory_get_usage())));

    $parsed = Services::createTopParser()->parse(file_get_contents($inputFile));

    Files::mkdir($outputDir);

    Reports::writeFile($outputDir . '/stanzas.txt', 'stanzas', $parsed);
    Reports::writeFile($outputDir . '/tags.txt', 'tags', $parsed);
    Reports::writeFile($outputDir . '/advisor.txt', 'advisor', $parsed);

    Files::remove($outputDir . '/tag-*.tpl');
    Files::remove($outputDir . '/tag-*.tree');
    foreach ($parsed->findAll('stanza:tag') as $tag) {
      $string = (string) $tag;
      $id = md5($string);
      $parsedTag = Services::createTagParser()->parse($string);
      file_put_contents("$outputDir/tag-$id.tpl", $tag);
      Reports::writeFile("$outputDir/tag-$id.tree", 'tree', $parsedTag);
    }
  }

}
