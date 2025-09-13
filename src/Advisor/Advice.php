<?php

namespace Civi\SmartyUp\Advisor;

class Advice {

  /**
   * @var string|null
   *   'ok' or 'problem' or 'suggestion'
   */
  protected $type = NULL;

  protected string $message;

  private string $tag;

  protected ?array $replacements = NULL;

  public static function createOK(string $message, string $tag) {
    return new static($message, $tag, 'ok');
  }

  public static function createProblem(string $message, string $tag) {
    return new static($message, $tag, 'problem');
  }

  public static function createSuggestion(string $message, string $tag, array $replacements) {
    $result = new static($message, $tag, 'suggestion');
    $result->replacements = $replacements;
    return $result;
  }

  public function __construct(string $message, string $tag, ?string $type = NULL) {
    $this->message = $message;
    $this->tag = $tag;
    $this->type = $type;
  }

  public function getMessage(): string {
    return $this->message;
  }

  public function getTag(): string {
    return $this->tag;
  }

  /**
   * @return null
   */
  public function getType() {
    return $this->type;
  }

  public function getId(): string {
    return md5($this->message . chr(0) . $this->tag);
  }

  public function getReplacements(): ?array {
    return $this->replacements;
  }

}
