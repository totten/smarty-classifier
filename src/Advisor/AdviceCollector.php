<?php

namespace Civi\SmartyUp\Advisor;

use Civi\SmartyUp\Advisor\Advice\Advice;
use Civi\SmartyUp\Advisor\Advice\AdviceOk;
use Civi\SmartyUp\Advisor\Advice\AdviceProblem;
use Civi\SmartyUp\Advisor\Advice\AdviceSuggestion;

class AdviceCollector {

  /**
   * @var Advice[]
   */
  public $results = [];

  public function add(Advice $advice): void {
    if ($advice instanceof AdviceSuggestion) {
      if ($advice->getReplacements() === [$advice->getTag()] || empty($advice->getReplacements())) {
        $advice = new AdviceProblem($advice->getMessage(), $advice->getTag());
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
