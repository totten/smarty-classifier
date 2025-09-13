<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\Advice;

class CheckTagEvent {

  /**
   * @var \ParserGenerator\SyntaxTreeNode\Root|false
   */
  public $tag;

  /**
   * Original, unparsed content of the tag.
   *
   * @var string
   */
  public string $original;

  public $advices = [];

  /**
   * @param string $originalTag
   * @param \ParserGenerator\SyntaxTreeNode\Root|false $parsedTag
   */
  public function __construct(string $originalTag, $parsedTag) {
    $this->original = $originalTag;
    $this->tag = $parsedTag;
  }

  public function isTagType(string $tagType): bool {
    return $this->getTagType() === $tagType;
  }

  public function getTagType(): ?string {
    return $this->tag ? $this->tag->findFirst('tag')->getDetailType() : NULL;
  }

  public function getTagId(): ?string {
    return $this->tag ? spl_object_hash($this->tag) : NULL;
  }

  public function finalize(): void {
    if (empty($this->advices)) {
      $this->advices[] = Advice::createOK('OK: No errors reported', (string) $this->tag);
    }
  }

}
