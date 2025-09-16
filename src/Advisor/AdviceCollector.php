<?php

namespace Civi\SmartyUp\Advisor;

class AdviceCollector {

  /**
   * @var \Civi\SmartyUp\Advisor\Advice[]
   */
  public $results = [];

  public function add(Advice $advice): void {
    $this->results[$advice->getId()] = $advice;
  }

  public function filter(callable $filter): array {
    return array_filter($this->results, $filter);
  }

  public function getDistinctMessages(): array {
    return array_unique(array_map(fn($a) => $a->getMessage(), $this->results));
  }

}
