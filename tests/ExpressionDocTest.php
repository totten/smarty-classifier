<?php

namespace Civi\SmartyUp;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExpressionDocTest extends TestCase {

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
    $origDoc = new ExpressionDoc($origTxt);
    $newDoc = $origDoc->withNofilter();
    $this->assertEquals($expectTxt, (string) $newDoc);
  }

  public static function getFindModifiersExamples(): array {
    return [
      ['{$var}', NULL, []],
      ['{$var|lower}', NULL, [['lower']]],
      ['{$var|lower|upper|substr:1:-1}', NULL, [['lower'], ['upper'], ['substr', '1', '-1']]],
      ['{$var|smarty:"nodefaults"}', NULL, [['smarty', 'nodefaults']]],
    ];
  }

  /**
   * @param string $origTxt
   *   Ex: '{$var|lower|upper}'
   * @param string|null $filter
   * @param array $expect
   *  Ex: [['lower'], ['upper']]
   * @dataProvider getFindModifiersExamples
   */
  public function testFindModifiers(string $origTxt, ?string $filter, array $expect): void {
    $origDoc = new ExpressionDoc($origTxt);
    $actual = $origDoc->findModifiers($filter);
    $this->assertEquals($expect, $actual);
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
    $origDoc = new ExpressionDoc($origTxt);
    $newDoc = $origDoc->withModifier($newModifier);
    $this->assertEquals($expectTxt, (string) $newDoc);
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
    $origDoc = new ExpressionDoc($origTxt);
    $this->assertEquals($expectResult, $origDoc->hasNodefaults());
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
    $origDoc = new ExpressionDoc($origTxt);
    $newDoc = $origDoc->withoutNodefaults();
    $this->assertEquals($expectTxt, (string) $newDoc);
  }

  public function testFilterModifier() {
    $origDoc = new ExpressionDoc('{$foo|escape:"htmlall"}');
    $newDoc = $origDoc->filterModifiers(function($modifier, ...$args) {
      if ($modifier === 'escape') {
        $args = preg_replace('/^htmlall$/', 'html', $args);
        return [$modifier, ...$args];
      }
    });
    $this->assertEquals('{$foo|escape:html}', (string) $newDoc);
  }

}
