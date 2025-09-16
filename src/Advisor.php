<?php

namespace Civi\SmartyUp;

use Civi\SmartyUp\Advisor\Advice;
use Civi\SmartyUp\Rule\ElseIfCheck;
use Civi\SmartyUp\Rule\ExpressionEscaping;
use Civi\SmartyUp\Rule\PrintedArgs;
use Civi\SmartyUp\Rule\UnknownBlock;
use Civi\SmartyUp\Rule\UnknownTagType;
use Civi\SmartyUp\Rule\UnparsedTag;

class Advisor {

  protected $adviceListener;

  protected array $rules;

  /**
   * @param callable $listener
   */
  public function __construct(callable $listener) {
    $this->adviceListener = $listener;
    $this->rules = [
      [new UnparsedTag(), 'checkTag'],
      [new UnknownTagType(), 'checkTag'],
      [new UnknownBlock(), 'checkTag'],
      [new PrintedArgs(), 'checkTag'],
      [new ElseIfCheck(), 'checkTag'],
      [new ExpressionEscaping(), 'checkTag'],
    ];
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

    foreach ($this->rules as $rule) {
      $rule($checkTag);
    }

    $checkTag->finalize();
    return $checkTag->advices;
  }

}
