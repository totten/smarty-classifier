<?php

namespace Civi\SmartyUp\Advisor\Advice;

class AdviceSuggestion extends Advice {

  private string $original;
  private array $replacements;

  public function __construct(string $message, string $original, array $replacements) {
    parent::__construct($message);
    $this->original = $original;
    $this->replacements = $replacements;
  }

  public function getOriginal(): string {
    return $this->original;
  }

  public function getReplacements(): array {
    return $this->replacements;
  }

}
