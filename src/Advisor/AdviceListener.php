<?php

namespace Civi\SmartyUp\Advisor;

interface AdviceListener {

  public function add(string $status, string $message, string $tagString, $suggest = NULL): void;

}
