<?php

namespace Civi\SmartyUp\Advisor;

class AdviceCollector implements AdviceListener {

  public $results = [];

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
