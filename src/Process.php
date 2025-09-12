<?php

namespace Civi\SmartyUp;

class Process {

  /**
   * Executes a callable in a forked child process.
   * The parent process waits for the child to finish. If the child
   * terminates abnormally, an error message is printed.
   *
   * @param callable $cb The callable to execute in the child process.
   *
   * @return void
   */
  public static function doAsChild(callable $cb): void {
    // The pcntl_fork() function creates a new process.
    // In the parent process, it returns the PID of the child.
    // In the child process, it returns 0.
    // If the fork fails, it returns -1.
    $pid = pcntl_fork();

    if ($pid === -1) {
      // Forking failed.
      echo "Error: Failed to fork process.\n";
      return;
    }
    elseif ($pid === 0) {
      // We are in the child process.
      // Execute the provided callable.
      try {
        $cb();
      }
      catch (\Throwable $e) {
        // If the callable throws an exception, print the error and exit.
        // A non-zero exit code signals an abnormal termination.
        echo "Child process error: " . $e->getMessage() . "\n";
        exit(1);
      }

      // Exit the child process normally.
      exit(0);
    }
    else {
      // We are in the parent process.
      // Wait for the child process to finish.
      pcntl_waitpid($pid, $status);

      // Check if the child process exited abnormally.
      if (pcntl_wifexited($status) && pcntl_wexitstatus($status) !== 0) {
        throw new \RuntimeException("Error: Child process terminated abnormally with status " . pcntl_wexitstatus($status));
      }
    }
  }

}
