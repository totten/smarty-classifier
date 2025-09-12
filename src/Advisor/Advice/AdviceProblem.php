<?php

namespace Civi\SmartyUp\Advisor\Advice;

class AdviceProblem extends Advice {

  private string $original;

  public function __construct(string $message, string $original) {
    parent::__construct($message);
    $this->original = $original;
  }

  public function getOriginal(): string {
    return $this->original;
  }

}
