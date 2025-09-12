<?php

namespace Civi\SmartyUp\Advisor\Advice;

class AdviceSuggestion extends Advice {

  private array $replacements;

  public function __construct(string $message, string $tag, array $replacements) {
    parent::__construct($message, $tag);
    $this->replacements = $replacements;
  }

  public function getReplacements(): array {
    return $this->replacements;
  }

}
