<?php

namespace Civi\SmartyUp\Advisor\Advice;

abstract class Advice {

  protected string $message;

  public function __construct(string $message) {
    $this->message = $message;
  }

  public function getMessage(): string {
    return $this->message;
  }

}
