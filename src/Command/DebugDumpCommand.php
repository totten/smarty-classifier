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
      ->addOption('threads', NULL, InputOption::VALUE_REQUIRED, 'Number threads in worker pool', 5)
      ->addOption('report', 'r', InputOption::VALUE_REQUIRED, 'Comma-separate list of reports to generate', 'stanzas,tags,advisor,tree');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $maxThreads = function_exists('pcntl_fork') ? $input->getOption('threads') : 1;

    $inputBaseDir = rtrim($input->getOption('input-dir'), '/');
    $outputBaseDir = rtrim($input->getOption('output-dir'), '/');

    $files = (new Finder)->in($inputBaseDir)->files()->name($input->getOption('name'));
    if ($maxThreads > 1) {
      return $this->executeWithPool($input, $output, $files, $inputBaseDir, $outputBaseDir, $maxThreads);
    }
    else {
      return $this->executeWithSingleThread($input, $output, $files, $inputBaseDir, $outputBaseDir);
    }
  }

  protected function executeWithSingleThread(InputInterface $input, OutputInterface $output, Finder $files, string $inputBaseDir, string $outputBaseDir) {
    $output->writeln('<info>Execute as single thread</info>', OutputInterface::VERBOSITY_VERBOSE);
    foreach ($files as $fileObj) {
      /** @var \SplFileInfo $fileObj */
      $inputFile = (string) $fileObj;

      $relativeFile = substr($inputFile, strlen($inputBaseDir) + 1);
      $outputDir = $outputBaseDir . '/' . $relativeFile . '.d';
      $this->processFile($inputFile, $outputDir, $input, $output);
    }
    // In this loop, we don't catch file-level errors. They propagate up. So completion is success.
    return 0;
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param \Symfony\Component\Finder\Finder $files
   * @param string $inputBaseDir
   * @param string $outputBaseDir
   * @param int $maxThreads
   *
   * @return int|void
   */
  protected function executeWithPool(InputInterface $input, OutputInterface $output, Finder $files, string $inputBaseDir, string $outputBaseDir, int $maxThreads) {
    $output->writeln("<info>Execute with thread pool ($maxThreads)</info>", OutputInterface::VERBOSITY_VERBOSE);
    $errors = 0;

    $pool = [];

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
        if (count($pool) >= $maxThreads) {
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
          $output->writeln("<info>Start $inputFile " . date('Y-m-d H:i:s') . "</info>", OutputInterface::VERBOSITY_VERBOSE);
          $this->processFile($inputFile, $outputDir, $input, $output);
          $output->writeln("<info>Finish $inputFile " . date('Y-m-d H:i:s') . "</info>", OutputInterface::VERBOSITY_VERBOSE);
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
