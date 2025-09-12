<?php

namespace Civi\SmartyUp\Advisor;

use Civi\SmartyUp\Advisor\Advice;

class AdviceCollector {

  /**
   * @var \Civi\SmartyUp\Advisor\Advice[]
   */
  public $results = [];

  public function add(Advice $advice): void {
    if ($advice->getReplacements() !== NULL) {
      if ($advice->getReplacements() === [$advice->getTag()] || empty($advice->getReplacements())) {
        $advice = Advice::createProblem($advice->getMessage(), $advice->getTag());
      }
    }
    $this->results[$advice->getId()] = $advice;
  }

  public function filter(callable $filter): array {
    return array_filter($this->results, $filter);
  }

  public function getDistinctMessages(): array {
    return array_unique(array_map(fn($a) => $a->getMessage(), $this->results));
  }

}
