<?php

namespace Civi\SmartyUp\Advisor\Advice;

class AdviceOk extends Advice {

  private string $tagString;

  public function __construct(string $message, string $tagString) {
    parent::__construct($message);
    $this->tagString = $tagString;
  }

  public function getTagString(): string {
    return $this->tagString;
  }

}
