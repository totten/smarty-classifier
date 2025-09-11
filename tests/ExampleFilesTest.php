<?php

namespace Civi\SmartyClassifier;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExampleFilesTest extends TestCase {

  public static function getInputBaseDir(?string $suffix = NULL): string {
    $prj = dirname(__DIR__);
    return "$prj/examples/input" . ($suffix === NULL ? '' : '/' . $suffix);
  }

  public static function getOutputBaseDir(?string $suffix = NULL): string {
    $prj = dirname(__DIR__);
    return "$prj/examples/output" . ($suffix === NULL ? '' : '/' . $suffix);
  }

  public function getExampleTemplates(): array {
    $cases = [];

    $files = glob(static::getInputBaseDir('/*.tpl'));
    foreach ($files as $file) {
      foreach (Reports::getReportList() as $name) {
        $cases[basename($file) . " $name"] = [basename($file), $name];
      }
    }

    return $cases;
  }

  /**
   * @param string $tplFile
   * @param string $name
   * @dataProvider getExampleTemplates
   */
  public function testExampleTemplates(string $tplFile, string $name): void {
    $parsed = Services::createTopParser()->parse(file_get_contents(static::getInputBaseDir($tplFile)));
    $expectReportFile = $tplFile . ".d/$name.txt";
    $expectReport = file_get_contents(static::getOutputBaseDir($expectReportFile));
    $actualReport = Reports::writeString($name, $parsed);
    $this->assertEquals($expectReport, $actualReport);
  }

  public function getExampleTags(): array {
    $cases = [];
    $files = glob(static::getOutputBaseDir('*/tag-*.tpl'));
    foreach ($files as $file) {
      $relFile = ltrim(substr($file, strlen(static::getOutputBaseDir())), '/');
      $cases[$relFile] = [$relFile, 'tree'];
    }
    return $cases;
  }

  /**
   * @param string $tplFile
   * @param string $name
   * @dataProvider getExampleTags
   */
  public function testExampleTags(string $tplFile, string $name): void {
    $inputFile = static::getOutputBaseDir($tplFile);
    $parsed = Services::createTagParser()->parse(file_get_contents($inputFile));
    $name = 'tree';

    $expectFile = preg_replace(';\.tpl$;', ".$name", $inputFile);
    $expectReport = file_get_contents($expectFile);
    $actualReport = Reports::writeString($name, $parsed);
    $this->assertEquals($expectReport, $actualReport);
  }

}
