<?php

namespace Civi\SmartyUp\Advisor;

interface AdviceListener {

  public function addOk(string $message, string $tagString): void;

  public function addSuggestion(string $message, string $original, array $replacements): void;

  public function addProblem(string $message, string $original): void;

}
