<?php
namespace Civi\SmartyUp;

use LesserEvil\ShellVerbosityIsEvil;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Symfony\Component\Console\Application {

  /**
   * Primary entry point for execution of the standalone command.
   */
  public static function main(string $name, ?string $binDir, array $argv) {
    $preBootInput = new ArgvInput($argv);
    $preBootOutput = new ConsoleOutput();
    SmartyUp::ioStack()->push($preBootInput, $preBootOutput);

    try {
      $application = new static($name);
      SmartyUp::ioStack()->replace('app', $application);
      $application->configure();
      $result = $application->run(SmartyUp::input(), SmartyUp::output());
    }
    finally {
      SmartyUp::ioStack()->pop();
    }

    exit($result);
  }

  public function configure() {
    $this->setCatchExceptions(TRUE);
    $this->setAutoExit(FALSE);
    $this->add(new Command\ParseCommand());
    $this->add(new Command\DebugAdvisorCommand());
    $this->add(new Command\DebugStanzasCommand());
    $this->add(new Command\DebugTagsCommand());
    $this->add(new Command\DebugDumpCommand());
  }

  protected function configureIO(InputInterface $input, OutputInterface $output) {
    ShellVerbosityIsEvil::doWithoutEvil(function() use ($input, $output) {
      parent::configureIO($input, $output);
    });
  }

  public function run(?InputInterface $input = NULL, ?OutputInterface $output = NULL) {
    $input = $input ?: new ArgvInput();
    $output = $output ?: new ConsoleOutput();

    try {
      SmartyUp::ioStack()->push($input, $output);
      return parent::run($input, $output);
    }
    finally {
      SmartyUp::ioStack()->pop();
    }
  }

}
