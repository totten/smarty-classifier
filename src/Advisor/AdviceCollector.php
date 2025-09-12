<?php

namespace Civi\SmartyUp\Advisor;

class AdviceCollector implements AdviceListener {

  public $results = [];

  public function addOk(string $message, string $tagString): void {
    $this->add('ok', $message, $tagString);
  }

  public function addProblem(string $message, string $tagString): void {
    $this->add('problem', $message, $tagString);
  }

  public function addSuggestion(string $message, string $original, array $replacements): void {
    if ($replacements === [$original] || empty($replacements)) {
      $this->add('problem', $message, $original);
    }
    else {
      $this->add('suggestion', $message, $original, $replacements);
    }
  }

  public function add(string $status, string $message, string $tagString, $suggest = NULL): void {
    $id = md5($status . chr(0) . $message . chr(0) . $tagString);
    $this->results[$id] = [
      'status' => $status,
      'message' => $message,
      'tag' => $tagString,
      'suggest' => $suggest,
    ];
  }

  public function filter(callable $filter): array {
    return array_filter($this->results, $filter);
  }

  public function getDistinct(string $column): array {
    return array_unique(array_column($this->results, $column));
  }

}
