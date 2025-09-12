<?php

namespace Civi\SmartyUp\Console;

use Symfony\Component\Console\Output\Output;

class FileHandleOutput extends Output {

  protected $fh;

  /**
   * @param $fh
   */
  public function __construct($fh) {
    parent::__construct();
    $this->fh = $fh;
  }

  protected function doWrite(string $message, bool $newline) {
    $message = $newline ? "$message\n" : $message;
    fwrite($this->fh, $message);
  }

}
