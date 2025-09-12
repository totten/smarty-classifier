<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Files;
use Civi\SmartyUp\Process;
use Civi\SmartyUp\Reports;
use Civi\SmartyUp\Services;
use Symfony\Component\Finder\Finder;

class ScanExamplesCommand {

  public function run(array $argv): int {
    [$inDir, $outDir] = $this->parseArgs($argv);
    return $this->processDir($inDir, $outDir);
  }

  private function parseArgs(array $argv): array {
    $prog = array_shift($argv);

    if (!empty($argv) && count($argv) >= 2) {
      return [rtrim($argv[0], '/'), rtrim($argv[1], '/')];
    }
    else {
      $prj = dirname(dirname(__DIR__));
      return ["$prj/examples/input", "$prj/examples/output"];
    }
  }

  private function processDir(string $inputBaseDir, string $outputBaseDir): int {
    Services::createTopParser();
    Services::createTagParser();

    $files = (new Finder)->in($inputBaseDir)->files()->name('*.tpl');
    $errors = 0;

    foreach ($files as $fileObj) {
      /** @var \SplFileInfo $fileObj */
      $inputFile = (string) $fileObj;
      $relativeFile = substr($inputFile, strlen($inputBaseDir) + 1);
      $outputDir = $outputBaseDir . '/' . $relativeFile . '.d';
      try {
        Process::doAsChild(fn() => $this->processFile($inputFile, $outputDir));
      }
      catch (\Throwable $e) {
        fwrite(STDERR, "\nERROR ($inputFile): " . $e->getMessage() . "\n\n");
        $errors++;
      }
    }

    echo "\n";
    return $errors === 0 ? 0 : 1;
  }

  private function processFile(string $inputFile, string $outputDir): void {
    // printf("Process %s => %s\n", $inputFile, $outputDir);
    printf("Process %s => %s (%s)\n", $inputFile, $outputDir, number_format(memory_get_usage()));
    // echo '.';

    $parsed = Services::createTopParser()->parse(file_get_contents($inputFile));

    Files::mkdir($outputDir);

    foreach (Reports::getReportList() as $name) {
      Reports::writeFile($outputDir . '/' . "$name.txt", $name, $parsed);
    }

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
