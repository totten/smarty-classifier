<?php

namespace Civi\SmartyUp\Advisor;

use Civi\SmartyUp\Advisor\Advice\Advice;

interface AdviceListener {

  public function addAdvice(Advice $advice): void;

}
