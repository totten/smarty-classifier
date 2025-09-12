<?php

namespace Civi\SmartyUp\Advisor\Advice;

abstract class Advice {

  protected string $message;

  private string $tag;

  public function __construct(string $message, string $tag) {
    $this->message = $message;
    $this->tag = $tag;
  }

  public function getMessage(): string {
    return $this->message;
  }

  public function getTag(): string {
    return $this->tag;
  }

  public function getId(): string {
    return md5($this->message . chr(0) . $this->tag);
  }

}
