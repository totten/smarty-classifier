<?php
namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\Files;
use Civi\SmartyUp\Reports;
use Civi\SmartyUp\Services;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class DebugDumpCommand extends Command {

  protected function configure() {
    $prj = dirname(dirname(__DIR__));
    $inDir = "$prj/examples/input";
    $outDir = "$prj/examples/output";

    $this->setName('debug:dump')
      ->setDescription('Scan a tree with TPL files. Write a set of report-files.')
      ->addOption('input-dir', 'i', InputOption::VALUE_REQUIRED, 'The input directory', $inDir)
      ->addOption('output-dir', 'o', InputOption::VALUE_REQUIRED, 'The output directory', $outDir)
      ->addOption('name', 'N', InputOption::VALUE_REQUIRED, 'Filter by tpl name', '*.tpl')
      ->addOption('report', 'r', InputOption::VALUE_REQUIRED, 'Comma-separate list of reports to generate', 'stanzas,tags,advisor,tree');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!function_exists('pcntl_fork')) {
      $output->writeln("<error>This command requires the 'pcntl' extension.</error>");
      return 1;
    }

    $inputBaseDir = rtrim($input->getOption('input-dir'), '/');
    $outputBaseDir = rtrim($input->getOption('output-dir'), '/');

    Services::createTopParser();
    Services::createTagParser();

    $files = (new Finder)->in($inputBaseDir)->files()->name($input->getOption('name'));
    $errors = 0;

    $pool = [];
    $maxProcs = 5;

    foreach ($files as $fileObj) {
      /** @var \SplFileInfo $fileObj */
      $inputFile = (string) $fileObj;

      $pid = pcntl_fork();
      if ($pid === -1) {
        // Could not fork. Maybe run sequentially? For now, just error out.
        $output->writeln("<error>Failed to fork process. Aborting.</error>");
        // Wait for any running children before aborting
        foreach (array_keys($pool) as $runningPid) {
          pcntl_waitpid($runningPid, $status);
        }
        return 1;
      }
      elseif ($pid) {
        // Parent process
        $pool[$pid] = $inputFile;
        if (count($pool) >= $maxProcs) {
          $status = NULL;
          $exitedPid = pcntl_wait($status);
          if (pcntl_wifexited($status) && pcntl_wexitstatus($status) !== 0) {
            $output->writeln(sprintf("\n<error>ERROR (%s): Child process terminated abnormally</error>\n", $pool[$exitedPid]));
            $errors++;
          }
          unset($pool[$exitedPid]);
        }
      }
      else {
        // Child process
        $relativeFile = substr($inputFile, strlen($inputBaseDir) + 1);
        $outputDir = $outputBaseDir . '/' . $relativeFile . '.d';
        try {
          $this->processFile($inputFile, $outputDir, $input, $output);
          exit(0);
        }
        catch (\Throwable $e) {
          // Writing to stderr from child.
          file_put_contents('php://stderr', sprintf("\nERROR (%s): %s\n\n", $inputFile, $e->getMessage()));
          exit(1);
        }
      }
    }

    // Wait for any remaining child processes
    while (!empty($pool)) {
      $status = NULL;
      $exitedPid = pcntl_wait($status);
      if (pcntl_wifexited($status) && pcntl_wexitstatus($status) !== 0) {
        $output->writeln(sprintf("\n<error>ERROR (%s): Child process terminated abnormally</error>\n", $pool[$exitedPid]));
        $errors++;
      }
      unset($pool[$exitedPid]);
    }

    $output->writeln("");
    return $errors === 0 ? 0 : 1;
  }

  private function processFile(string $inputFile, string $outputDir, InputInterface $input, OutputInterface $output): void {
    $output->writeln(sprintf("Process <comment>%s</comment> => <comment>%s</comment>", $inputFile, $outputDir));
    // $output->writeln(sprintf("Process <comment>%s</comment> => <comment>%s</comment> (<comment>%s</comment>)", $inputFile, $outputDir, number_format(memory_get_usage())));

    $parsed = Services::createTopParser()->parse(file_get_contents($inputFile));

    Files::mkdir($outputDir);

    $reports = explode(',', $input->getOption('report'));
    $reportCount = 0;
    foreach ($reports as $report) {
      switch ($report) {
        case 'stanzas':
        case 'tags':
        case 'advisor':
          $reportCount++;
          Reports::writeFile($outputDir . "/$report.txt", $report, $parsed);
          break;

        case 'tree':
          $reportCount++;
          Files::remove($outputDir . '/tag-*.tpl');
          Files::remove($outputDir . '/tag-*.tree');
          foreach ($parsed->findAll('stanza:tag') as $tag) {
            $string = (string) $tag;
            $id = md5($string);
            $parsedTag = Services::createTagParser()->parse($string);
            file_put_contents("$outputDir/tag-$id.tpl", $tag);
            Reports::writeFile("$outputDir/tag-$id.tree", 'tree', $parsedTag);
          }
          break;

        default:
          $output->getErrorOutput()->writeln("<error>SKIP: Unrecognized report: $report</error>");
          break;
      }
    }

    if (empty($reportCount)) {
      throw new \RuntimeException("No valid reports were requested!");
    }
  }

}
