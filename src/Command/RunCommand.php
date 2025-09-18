<?php

namespace Civi\SmartyUp\Command;

use Civi\SmartyUp\ActionPrompt;
use Civi\SmartyUp\Advisor;
use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\Arrays;
use Civi\SmartyUp\Services;
use Civi\SmartyUp\SmartyUp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class RunCommand extends Command {

  protected function configure() {
    $this->setName('run')
      ->setDescription('Parse the tags in Smarty document')
      ->addArgument('files', InputArgument::IS_ARRAY, 'List of files to parse. (If omitted, scan STDIN.)');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $files = $input->getArgument('files');
    foreach ($files as $file) {
      if (is_dir($file)) {
        $finder = (new Finder())->in($file)->files()->name('*.tpl');
        foreach ($finder as $fileObj) {
          /** @var \SplFileInfo $fileObj */
          $this->runFile((string) $fileObj);
        }
      }
      elseif (is_file($file)) {
        $this->runFile($file);
      }
      else {
        $output->writeln("<error>SKIP: $file</error>");
      }
    }
    return 0;
  }

  protected function runFile(string $file): void {
    $io = SmartyUp::io();
    $io->title($file);

    $oldContent = file_get_contents($file);
    $topParser = Services::createTopParser();
    $doc = $topParser->parse($oldContent);

    $updateCount = 0;
    $updateContent = function (string $old, string $new) use ($file, &$updateCount) {
      // We prefer to re-read before each update. This UX pauses, and the user may make manual changes...
      $content = file_get_contents($file);
      $newContent = str_replace($old, $new, $content);
      if (!file_put_contents($file, $newContent)) {
        throw new \RuntimeException("Failed to write $file");
      }
      $updateCount++;
    };
    $buckets = ['ok' => [], 'problem' => [], 'replace-auto' => [], 'replace-prompt' => []];

    $advisor = new Advisor(function (Advice $a) use (&$buckets) {
      switch ($a->getType()) {
        case 'ok':
        case 'problem':
          $buckets[$a->getType()][] = $a;
          break;

        case 'suggestion':
          if (count($a->getReplacements()) === 1) {
            $buckets['replace-auto'][] = $a;
          }
          else {
            $buckets['replace-prompt'][] = $a;
          }
          break;

        default:
          throw new \RuntimeException("Unrecognized advice type");
      }
    });
    $advisor->scanDocument($doc);

    $io->section('Summary');
    $io->text("Finished scanning \"<comment>$file</comment>\". Key results:");
    $io->newLine();
    $summary = [
      sprintf('<info>OK</info>: <comment>%d</comment> tag(s) looked OK. They did not raise red flags.', count($buckets['ok'])),
      sprintf('<info>Updates</info>: <comment>%d</comment> tag(s) have updates, of which <comment>%d</comment> tag(s) need clarification.',
        count($buckets['replace-auto']) + count($buckets['replace-prompt']), count($buckets['replace-prompt'])),
      sprintf('<info>Problems</info>: <comment>%d</comment> tag(s) looked problematic. You will need to inspect separately.', count($buckets['problem'])),
    ];
    $io->listing($summary);
    $this->promptContinue();

    if (!empty($buckets['ok'])) {
      $io->section('Accepted Tags');
      $io->text(sprintf('The following <comment>%d</comment> tag(s) were accepted. You may wish to skim them to double-check.', count($buckets['ok'])));
      $io->newLine();

      $okGroups = Arrays::groupBy($buckets['ok'], fn($a) => $a->getTag());
      ksort($okGroups);
      $rows = [];
      foreach ($okGroups as $tag => $advices) {
        $rows[] = ['<info>' . count($advices) . 'x</info>', "<comment>$tag</comment>"];
      }
      $io->table(['', 'Tag'], $rows);
      $this->promptContinue();
    }

    if (!empty($buckets['replace-auto'])) {
      $io->section('Replacements (Automatic)');
      $io->text('The following tags can be updated automatically:');
      $io->newLine();
      foreach ($buckets['replace-auto'] as $advice) {
        /** @var \Civi\SmartyUp\Advisor\Advice $advice */
        $io->text("<info>Old</info>: " . $advice->getTag());
        $io->text("<info>New</info>: " . $advice->getReplacements()[0]);
        $io->newLine();
      }

      ActionPrompt::create('Apply update(s)?')
        ->add('y', 'Yes', function () use ($buckets, $updateContent) {
          foreach ($buckets['replace-auto'] as $advice) {
            $updateContent($advice->getTag(), $advice->getReplacements()[0]);
          }
        })
        ->run('y');
    }

    if (!empty($buckets['replace-prompt'])) {
      $io->section('Replacements (Interactive)');
      foreach ($buckets['replace-prompt'] as $advice) {
        $io->text('<info>Tag</info>: ' . $advice->getTag());
        $io->text('<info>Message</info>: ' . $advice->getMessage());
        $prompt = ActionPrompt::create('Which version should we use?');
        foreach ($advice->getReplacements() as $n => $replacement) {
          $prompt->add(1 + $n, "Update to: <comment>$replacement</comment>",
            fn() => $updateContent($advice->getTag(), $replacement)
          );
        }
        $prompt->run('s');
      }
    }

    if (!empty($buckets['problem'])) {
      $io->section('Problems');
      $problemGroups = Arrays::groupBy($buckets['problem'], fn($a) => $a->getMessage());

      $io->text(sprintf(
        'Some problems cannot be handled automatically. We found %d issue(s).',
        count($buckets['problem'])
      ));
      $io->newLine();

      foreach ($problemGroups as $message => $items) {
        $io->text(sprintf('[<info>%dx</info>] <comment>%s</comment>', count($items), $message));
        $io->newLine();
        foreach ($items as $n => $a) {
          $io->write('   ' . (1 + $n) . '. ');
          $io->write('<comment>');
          $io->write($a->getTag(), FALSE, OutputInterface::OUTPUT_PLAIN);
          $io->writeln('</comment>');
          // $io->writeln('   ' . (1 + $n) . '. ' . $a->getTag());
        }
        $io->newLine();
      }

      $io->text('These cannot be updated automatically. You should inspect the file manually.');
      $this->promptContinue();
    }

    $io->section('Finished');

    $io->text(sprintf('Applied <comment>%d</comment> update(s) to "<comment>%s</comment>".', $updateCount, $file));
  }

  protected function promptContinue($message = 'Continue with review process?'): void {
    if (!SmartyUp::io()->confirm($message)) {
      throw new \RuntimeException('User aborted');
    }
  }

}
