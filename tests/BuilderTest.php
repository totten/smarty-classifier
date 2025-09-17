<?php

namespace Civi\SmartyUp;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class BuilderTest extends TestCase {

  public static function getNofilterExamples(): array {
    return [
      ['{$var}', '{$var nofilter}'],
      ['{$var|escape}', '{$var|escape nofilter}'],
      ['{$var|lower|escape:html|escape:"url"}', '{$var|lower|escape:html|escape:"url" nofilter}'],
    ];
  }

  /**
   * @param string $origTxt
   * @param string $expectTxt
   *
   * @dataProvider getNofilterExamples
   */
  public function testWithNofilter(string $origTxt, string $expectTxt) {
    $origTag = Services::createTagParser()->parse($origTxt);
    $newTag = Builder::withNofilter($origTag);
    $newTagTxt = (string) $newTag;
    $this->assertEquals($expectTxt, $newTagTxt);
  }

  public static function getModifierExamples(): array {
    return [
      ['{$var}', '|escape', '{$var|escape}'],
      ['{$var|lower}', '|escape:url', '{$var|lower|escape:url}'],
      ['{$var|lower nofilter}', '|escape:url', '{$var|lower|escape:url nofilter}'],
    ];
  }

  /**
   * @param string $origTxt
   * @param string $newModifier
   * @param string $expectTxt
   *
   * @dataProvider getModifierExamples
   */
  public function testWithModifier(string $origTxt, string $newModifier, string $expectTxt): void {
    $origTag = Services::createTagParser()->parse($origTxt);
    $newTag = Builder::withModifier($origTag, $newModifier);
    $newTagTxt = (string) $newTag;
    $this->assertEquals($expectTxt, $newTagTxt);
  }

}
