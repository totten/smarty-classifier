<?php

namespace Civi\SmartyUp\Advisor;

use Civi\SmartyUp\Advisor\Advice\Advice;
use Civi\SmartyUp\Advisor\Advice\AdviceOk;
use Civi\SmartyUp\Advisor\Advice\AdviceProblem;
use Civi\SmartyUp\Advisor\Advice\AdviceSuggestion;

class AdviceCollector implements AdviceListener {

  public $results = [];

  public function addAdvice(Advice $advice): void {
    if ($advice instanceof AdviceOk) {
      $this->add('ok', $advice->getMessage(), $advice->getTagString());
    }
    elseif ($advice instanceof AdviceProblem) {
      $this->add('problem', $advice->getMessage(), $advice->getOriginal());
    }
    elseif ($advice instanceof AdviceSuggestion) {
      if ($advice->getReplacements() === [$advice->getOriginal()] || empty($advice->getReplacements())) {
        $this->add('problem', $advice->getMessage(), $advice->getOriginal());
      }
      else {
        $this->add('suggestion', $advice->getMessage(), $advice->getOriginal(), $advice->getReplacements());
      }
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
