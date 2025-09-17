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

  public static function getHasNodefaults(): array {
    return [
      ['{$var}', FALSE],
      ['{$var|smarty:nodefaults}', TRUE],
      ['{$var.yadda[123]|lower|smarty:"nodefaults"|upper}', TRUE],
    ];
  }

  /**
   * @param string $origTxt
   * @param bool $expectResult
   * @dataProvider getHasNodefaults
   */
  public function testHasNodefaults(string $origTxt, bool $expectResult): void {
    $origTag = Services::createTagParser()->parse($origTxt);
    $actualResult = Builder::hasNodefaults($origTag);
    $this->assertEquals($expectResult, $actualResult);
  }

  public static function getWithoutNodefaults(): array {
    return [
      ['{$var}', '{$var}'],
      ['{$var|smarty:nodefaults}', '{$var}'],
      ['{$var.yadda[123]|lower|smarty:"nodefaults"|upper nofilter}', '{$var.yadda[123]|lower|upper nofilter}'],
    ];
  }

  /**
   * @param string $origTxt
   * @param string $expectTxt
   * @dataProvider getWithoutNodefaults
   */
  public function testWithoutNodefaults(string $origTxt, string $expectTxt): void {
    $origTag = Services::createTagParser()->parse($origTxt);
    $newTag = Builder::withoutNodefaults($origTag);
    $newTagTxt = (string) $newTag;
    $this->assertEquals($expectTxt, $newTagTxt);
  }

}
