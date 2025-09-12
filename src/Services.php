<?php

namespace Civi\SmartyUp;

use ParserGenerator\Parser;

class Services {

  protected static $instances = [];

  public static function createTopParser(): Parser {
    if (!isset(self::$instances[__FUNCTION__])) {
      $grammarDir = dirname(__DIR__);
      $grammar = static::readGrammarFile('top.txt')
        . "\n" . static::readGrammarFile('common.txt');
      self::$instances[__FUNCTION__] = new \ParserGenerator\Parser($grammar);
    }
    return self::$instances[__FUNCTION__];
  }

  public static function createTagParser(): Parser {
    if (!isset(self::$instances[__FUNCTION__])) {

      $grammar = static::readGrammarFile('tag.txt')
        . "\n" . static::readGrammarFile('common.txt');
      self::$instances[__FUNCTION__] = new \ParserGenerator\Parser($grammar);
    }
    return self::$instances[__FUNCTION__];
  }

  private static function readGrammarFile(string $name): string {
    $grammarDir = dirname(__DIR__);
    $r = file_get_contents("$grammarDir/grammar/" . $name);
    $lines = explode("\n", $r);
    $lines = preg_grep(';^\s*#;', $lines, PREG_GREP_INVERT);
    return implode("\n", $lines);
  }

}
