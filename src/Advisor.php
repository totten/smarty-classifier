<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\Rule\ExpressionEscaping;
use Civi\SmartyUp\Rule\PrintedArgs;
use Civi\SmartyUp\Rule\UnknownBlock;
use Civi\SmartyUp\Rule\UnknownTagType;
use Civi\SmartyUp\Rule\UnparsedTag;

class Advisor {

  protected $adviceListener;

  /**
   * @param callable $listener
   */
  public function __construct(callable $listener) {
    $this->adviceListener = $listener;
  }

  public function add(?Advice $advice): void {
    if ($advice) {
      call_user_func($this->adviceListener, $advice);
    }
  }

  public function scanString(string $content): void {
    $parsedDoc = Services::createTopParser()->parse($content);
    $this->scanDocument($parsedDoc);
  }

  public function scanDocument($parsedDoc): void {
    $tagParser = Services::createTagParser();
    foreach ($parsedDoc->findAll('stanza:tag') as $k => $stanza) {
      $tagString = (string) $stanza;
      $parsedTag = $tagParser->parse($tagString);
      foreach ($this->scanTag($tagString, $parsedTag) as $advice) {
        $this->add($advice);
      }
    }
  }

  /**
   * @param string $originalTag
   * @param \ParserGenerator\SyntaxTreeNode\Root|false $parsedTag
   *
   * @return void
   */
  protected function scanTag(string $originalTag, $parsedTag): array {
    $checkTag = new CheckTagEvent($originalTag, $parsedTag);

    $rules = [
      [new UnparsedTag(), 'checkTag'],
      [new UnknownTagType(), 'checkTag'],
      [new UnknownBlock(), 'checkTag'],
      [new PrintedArgs(), 'checkTag'],
      [new ExpressionEscaping(), 'checkTag'],
    ];
    foreach ($rules as $rule) {
      $rule($checkTag);
    }

    $checkTag->finalize();
    return $checkTag->advices;
  }

}
