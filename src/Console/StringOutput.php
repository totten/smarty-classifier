<?php

namespace Civi\SmartyUp\Console;

use Symfony\Component\Console\Output\Output;

class StringOutput extends Output {

  protected array $buffer = [];

  protected function doWrite(string $message, bool $newline) {
    $message = $newline ? "$message\n" : $message;
    $this->buffer[] = $message;
  }

  public function flush(): string {
    $result = implode("", $this->buffer);
    $this->buffer = [];
    return $result;
  }

}
