<?php

namespace Civi\SmartyClassifier;

use ParserGenerator\Parser;

class Services {

  protected static $instances = [];

  public static function createTopParser(): Parser {
    if (!isset(self::$instances[__FUNCTION__])) {
      $grammarDir = dirname(__DIR__);
      $grammar = file_get_contents("$grammarDir/grammar/top.txt")
        . "\n" . file_get_contents("$grammarDir/grammar/common.txt");
      self::$instances[__FUNCTION__] = new \ParserGenerator\Parser($grammar);
    }
    return self::$instances[__FUNCTION__];
  }

  public static function createTagParser(): Parser {
    if (!isset(self::$instances[__FUNCTION__])) {
      $grammarDir = dirname(__DIR__);
      $grammar = file_get_contents("$grammarDir/grammar/tag.txt")
        . "\n" . file_get_contents("$grammarDir/grammar/common.txt");
      self::$instances[__FUNCTION__] = new \ParserGenerator\Parser($grammar);
    }
    return self::$instances[__FUNCTION__];
  }

}
